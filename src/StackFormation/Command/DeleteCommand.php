<?php

namespace StackFormation\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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
                InputArgument::REQUIRED,
                'Stack'
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->interact_askForLiveStack($input, $output);

        $stack = $input->getArgument('stack');

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion("Are you sure you want to delete '$stack'? [y/N] ", false);

        if (!$helper->ask($input, $output, $question)) {
            throw new \Exception('Operation aborted');
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stack = $input->getArgument('stack');
        $this->stackManager->deleteStack($stack);
        $output->writeln("Triggered deletion of stack '$stack'.");
    }

}