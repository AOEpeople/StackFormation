<?php

namespace StackFormation\Command;

use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TemplateDiffCommand extends AbstractCommand
{

    protected function configure()
    {
        $this
            ->setName('stack:diff')
            ->setDescription('Compare the local template and input parameters with the current live stack')
            ->addArgument(
                'stack',
                InputArgument::REQUIRED,
                'Stack'
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->interactAskForConfigStack($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stack = $input->getArgument('stack');

        $effectiveStackName = $this->stackManager->getConfig()->getEffectiveStackName($stack);

        $parameters_live = $this->stackManager->getParameters($effectiveStackName);
        $parameters_local = $this->stackManager->getParametersFromConfig($effectiveStackName, true, true);

        ksort($parameters_live);
        ksort($parameters_local);



        $formatter = new FormatterHelper();
        $output->writeln("\n" . $formatter->formatBlock(['Parameters:'], 'error', true) . "\n");

        $returnVar = $this->printDiff(
            $this->arrayToString($parameters_live),
            $this->arrayToString($parameters_local)
        );
        if ($returnVar == 0) {
            $output->writeln('No changes'."\n");
        }

        $formatter = new FormatterHelper();
        $output->writeln("\n" . $formatter->formatBlock(['Template:'], 'error', true) . "\n");
        $returnVar = $this->printDiff(
            trim($this->stackManager->getTemplate($effectiveStackName)),
            trim($this->stackManager->getPreprocessedTemplate($stack))
        );
        if ($returnVar == 0) {
            $output->writeln('No changes'."\n");
        }
    }

    protected function arrayToString(array $a)
    {
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
}
