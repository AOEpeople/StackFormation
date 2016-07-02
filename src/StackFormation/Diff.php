<?php

namespace StackFormation;

use Aws\CloudFormation\Exception\CloudFormationException;
use StackFormation\Helper\Div;

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
        $parametersBlueprint = $this->blueprint->getParameters(true);
        $parametersBlueprint = Div::flatten($parametersBlueprint, 'ParameterKey', 'ParameterValue');
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
            if ($this->output->isVerbose()) { $this->output->writeln($this->stack->getName(). ': Comparing parameters'); }
            $parametersStack = $this->stack->getParameters();
            $parametersBlueprint = $this->blueprint->getParameters(true);
            $parametersBlueprint = Div::flatten($parametersBlueprint, 'ParameterKey', 'ParameterValue');

            $tmp['parameters'] = $this->parametersAreEqual($parametersStack, $parametersBlueprint) ? "<fg=green>equal</>" : "<fg=red>different</>";

            // template
            if ($this->output->isVerbose()) { $this->output->writeln($this->stack->getName(). ': Comparing template'); }
            $templateStack = trim($this->stack->getTemplate());
            $templateBlueprint = trim($this->blueprint->getPreprocessedTemplate());

            $templateStack = $this->normalizeJson($templateStack);
            $templateBlueprint = $this->normalizeJson($templateBlueprint);
            $tmp['template'] = ($templateStack === $templateBlueprint) ? "<fg=green>equal</>" : "<fg=red>different</>";
        } catch (CloudFormationException $e) {
            $tmp['parameters'] = 'Stack not found';
            $tmp['template'] = 'Stack not found';
        } catch (\Exception $e) {
            $tmp['parameters'] = '<fg=red>EXCEPTION: ' . $e->getMessage(). '</>';
            $tmp['template'] = 'EXCEPTION';
        }
        return $tmp;
    }

    protected function loadOriginalEnvVars(Stack $stack)
    {
        $vars = $stack->getUsedEnvVars();
        foreach ($vars as $var => $value) {
            $string = "$var=$value";
            // echo "Loading env var: $string\n";
            putenv($string);
        }
    }
    
    protected function parametersAreEqual(array $paramA, array $paramB)
    {
        // skip password fields
        while (($passWordKeyInA = array_search('****', $paramA)) !== false) {
            unset($paramA[$passWordKeyInA]);
            unset($paramB[$passWordKeyInA]);
        }
        while (($passWordKeyInB = array_search('****', $paramB)) !== false) {
            unset($paramA[$passWordKeyInB]);
            unset($paramB[$passWordKeyInB]);
        }

        foreach ($paramA as $key => $value) {
            if (isset($paramB[$key]) && $paramA[$key] != $paramB[$key]) {
                // try removing timestamps
                $normalizedValueA = preg_replace('/1[0-9]{9}/', '{tstamp}', $paramA[$key]);
                $normalizedValueB = preg_replace('/1[0-9]{9}/', '{tstamp}', $paramB[$key]);
                // and check again
                if ($normalizedValueA == $normalizedValueB) {
                    unset($paramA[$key]);
                    unset($paramB[$key]);
                }
            }
        }

        return $this->arrayToString($paramA) == $this->arrayToString($paramB);
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
        $data = json_decode($json, true);
        if (isset($data['Metadata'])) { unset($data['Metadata']); }
        if (isset($data['Description'])) { unset($data['Description']); }
        return json_encode($data, JSON_PRETTY_PRINT);
    }
}
