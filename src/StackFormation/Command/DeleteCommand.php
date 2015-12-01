<?php

namespace StackFormation\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class DeleteCommand extends AbstractCommand
{

    protected function configure()
    {
        $this
            ->setName('stack:delete')
            ->setDescription('Delete Stack')
            ->addArgument(
                'stack',
                InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                'Stack'
            )
            ->addOption(
                'except',
                null,
                InputOption::VALUE_OPTIONAL,
                'Stack that should NOT be deleted'
            )
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Skip asking for confirmation'
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->interact_askForLiveStack($input, $output, true, true);

        if (!$input->getOption('force')) {
            $stacks = "\n - " . implode("\n - ", $input->getArgument('stack'));
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion("Are you sure you want to delete following stacks? $stacks [y/N] ", false);
            if (!$helper->ask($input, $output, $question)) {
                throw new \Exception('Operation aborted');
            }
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stacks = $input->getArgument('stack');
        if (count($stacks) == 0) {
            $output->writeln("No stacks deleted.");
        }
        foreach ($stacks as $stack) {
            $this->stackManager->deleteStack($stack);
            $output->writeln("Triggered deletion of stack '$stack'.");
        }
    }

}