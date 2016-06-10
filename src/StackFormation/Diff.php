<?php

namespace StackFormation;

use Aws\CloudFormation\Exception\CloudFormationException;

class Diff
{
    protected $output;

    public function __construct(\Symfony\Component\Console\Output\OutputInterface $output)
    {
        $this->output = $output;
    }

    public function compare(Stack $stack, Blueprint $blueprint)
    {
        try {
            // parameters
            if (!$this->output->isQuiet()) {
                $this->output->writeln($stack->getName(). ': Comparing parameters');
            }
            $parametersStack = $stack->getParameters();
            $parametersBlueprint = $blueprint->getParameters(true, true);
            if ($this->compareParameters($parametersStack, $parametersBlueprint)) {
                $tmp['parameters'] = "<fg=green>equal</>";
            } else {
                $tmp['parameters'] = "<fg=red>different</>";
            }

            // template
            if (!$this->output->isQuiet()) {
                $this->output->writeln($stack->getName(). ': Comparing template');
            }
            $templateStack = trim($stack->getTemplate());
            $templateBlueprint = trim($blueprint->getPreprocessedTemplate());

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
    
    protected function compareParameters(array $a, array $b)
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
