<?php

namespace StackFormation;

use Aws\CloudFormation\Exception\CloudFormationException;

class Diff
{
    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $output;

    /**
     * @var Stack
     */
    protected $stack;

    /**
     * @var Blueprint
     */
    protected $blueprint;

    public function __construct(\Symfony\Component\Console\Output\OutputInterface $output)
    {
        $this->output = $output;
    }

    public function setStack(Stack $stack)
    {
        $this->stack = $stack;
        $this->loadOriginalEnvVars($stack);
        return $this;
    }

    public function setBlueprint(Blueprint $blueprint)
    {
        $this->blueprint = $blueprint;
        return $this;
    }

    public function diffParameters()
    {
        $parametersStack = $this->stack->getParameters();
        $parametersBlueprint = $this->blueprint->getParameters(true, true);
        if ($this->parametersAreEqual($parametersStack, $parametersBlueprint)) { // normalizes passwords!
            $this->output->writeln('No changes'."\n");
            return;
        }
        $returnVar = $this->printDiff(
            $this->arrayToString($parametersStack),
            $this->arrayToString($parametersBlueprint)
        );
        if ($returnVar == 0) {
            $this->output->writeln('No changes'."\n");
        }
    }

    public function diffTemplates()
    {

        $templateStack = trim($this->stack->getTemplate());
        $templateBlueprint = trim($this->blueprint->getPreprocessedTemplate());

        $templateStack = $this->normalizeJson($templateStack);
        $templateBlueprint = $this->normalizeJson($templateBlueprint);

        $returnVar = $this->printDiff(
            $templateStack,
            $templateBlueprint
        );
        if ($returnVar == 0) {
            $this->output->writeln('No changes'."\n");
        }
    }

    public function compare()
    {
        if (empty($this->stack)) {
            throw new \InvalidArgumentException('Stack not set');
        }
        if (empty($this->blueprint)) {
            throw new \InvalidArgumentException('Blueprint not set');
        }

        try {

            // parameters
            if (!$this->output->isQuiet()) {
                $this->output->writeln($this->stack->getName(). ': Comparing parameters');
            }
            $parametersStack = $this->stack->getParameters();
            $parametersBlueprint = $this->blueprint->getParameters(true, true);
            if ($this->parametersAreEqual($parametersStack, $parametersBlueprint)) {
                $tmp['parameters'] = "<fg=green>equal</>";
            } else {
                $tmp['parameters'] = "<fg=red>different</>";
            }

            // template
            if (!$this->output->isQuiet()) {
                $this->output->writeln($this->stack->getName(). ': Comparing template');
            }
            $templateStack = trim($this->stack->getTemplate());
            $templateBlueprint = trim($this->blueprint->getPreprocessedTemplate());

            $templateStack = $this->normalizeJson($templateStack);
            $templateBlueprint = $this->normalizeJson($templateBlueprint);

            if ($templateStack === $templateBlueprint) {
                $tmp['template'] = "<fg=green>equal</>";
            } else {
                $tmp['template'] = "<fg=red>different</>";
            }
        } catch (CloudFormationException $e) {
            $tmp['parameters'] = 'Stack not found';
            $tmp['template'] = 'Stack not found';
        } catch (\Exception $e) {
            $tmp['parameters'] = 'EXCEPTION ' . $e->getMessage();
            $tmp['template'] = 'EXCEPTION';
        }
        return $tmp;
    }

    protected function loadOriginalEnvVars(Stack $stack)
    {
        $vars = $stack->getUsedEnvVars();
        foreach ($vars as $var => $value) {
            $string = "$var=$value";
            echo "Loading env var: $string\n";
            putenv($string);
        }
    }
    
    protected function parametersAreEqual(array $a, array $b)
    {
        // skip password fields
        while (($passWordKeyInA = array_search('****', $a)) !== false) {
            unset($a[$passWordKeyInA]);
            unset($b[$passWordKeyInA]);
        }
        while (($passWordKeyInB = array_search('****', $b)) !== false) {
            unset($a[$passWordKeyInB]);
            unset($b[$passWordKeyInB]);
        }

        foreach ($a as $key => $value) {
            if (isset($b[$key]) && $a[$key] != $b[$key]) {
                // try removing timestamps
                $normalizedValueA = preg_replace('/1[0-9]{9}/', '{tstamp}', $a[$key]);
                $normalizedValueB = preg_replace('/1[0-9]{9}/', '{tstamp}', $b[$key]);
                // and check again
                if ($normalizedValueA == $normalizedValueB) {
                    unset($a[$key]);
                    unset($b[$key]);
                }
            }
        }

        return $this->arrayToString($a) == $this->arrayToString($b);
    }

    protected function arrayToString(array $a)
    {
        ksort($a);
        $lines = [];
        foreach ($a as $key => $value) {
            $lines[] = "$key: $value";
        }
        return implode("\n", $lines);
    }

    protected function printDiff($stringA, $stringB)
    {
        if ($stringA === $stringB) {
            return 0; // that's what diff would return
        }

        $fileA = tempnam(sys_get_temp_dir(), 'sfn_a_');
        file_put_contents($fileA, $stringA);

        $fileB = tempnam(sys_get_temp_dir(), 'sfn_b_');
        file_put_contents($fileB, $stringB);

        $command = is_file('/usr/bin/colordiff') ? 'colordiff' : 'diff';
        $command .= " -u $fileA $fileB";

        passthru($command, $returnVar);

        unlink($fileA);
        unlink($fileB);
        return $returnVar;
    }

    protected function normalizeJson($json)
    {
        return json_encode(json_decode($json, true), JSON_PRETTY_PRINT);
    }
}
