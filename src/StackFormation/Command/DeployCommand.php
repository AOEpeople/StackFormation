<?php

namespace StackFormation\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DeployCommand extends AbstractCommand
{

    protected function configure()
    {
        $this
            ->setName('stack:deploy')
            ->setDescription('Deploy Stack')
            ->addArgument(
                'stack',
                InputArgument::REQUIRED,
                'Stack'
            )
            ->addOption(
                'observe',
                'o',
                InputOption::VALUE_NONE,
                'Observe stack after'
            )
            ->addOption(
                'dryrun',
                'd',
                InputOption::VALUE_NONE,
                'Dry run'
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $dryRun = $input->getOption('dryrun');
        if ($dryRun) {
            $formatter = new \Symfony\Component\Console\Helper\FormatterHelper();
            $formattedBlock = $formatter->formatBlock(['Dry Run!'], 'error', true);
            $output->writeln("\n$formattedBlock\n");
        }
        $this->interactAskForConfigStack($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dryRun = $input->getOption('dryrun');
        $stack = $input->getArgument('stack');
        $this->stackManager->deployStack($stack, $dryRun);

        if (!$dryRun) {
            $effectiveStackName = $this->stackManager->getConfig()->getEffectiveStackName($stack);
            $output->writeln("Triggered deployment of stack '$effectiveStackName'.");

            if ($input->getOption('observe')) {
                return $this->stackManager->observeStackActivity($effectiveStackName, $output);
            } else {
                $output->writeln("\n-> Run this to observe the stack creation/update:");
                $output->writeln("{$GLOBALS['argv'][0]} stack:observe $effectiveStackName\n");
            }
        }
    }
}
