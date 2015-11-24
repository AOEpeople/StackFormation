<?php

namespace StackFormation\Command;

use StackFormation\Config;
use StackFormation\Command\AbstractCommand;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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

        $dialog = $this->getHelper('dialog');
        $confirmed = $dialog->askConfirmation(
            $output,
            "Are you sure you want to delete '$stack'? [y/N] ",
            false
        );
        if (!$confirmed) {
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