<?php

namespace StackFormation\Command\Stack;

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
            ->setDescription('delete Stack')
            ->addArgument(
                'stack',
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'Stack'
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $stacks = $input->getArgument('stack');
        if (count($stacks) == 0) {
            $dialog = $this->getHelper('dialog');
            /* @var $dialog \Symfony\Component\Console\Helper\DialogHelper */
            $stacksFromApi = array_keys($this->stackManager->getStacksFromApi());

            $stack = $dialog->select(
                $output,
                'Please select the stack you want to delete',
                $stacksFromApi
            );
            $input->setArgument('stack', [$stacksFromApi[$stack]]);
        }

        $stacks = $input->getArgument('stack');

        $dialog = $this->getHelper('dialog');
        $confirmed = $dialog->askConfirmation(
            $output,
            "Are you sure you want to delete ".implode(', ', $stacks)."?",
            false
        );
        if (!$confirmed) {
            throw new \Exception('Operation aborted');
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stacks = $input->getArgument('stack');
        foreach ($stacks as $stack) {
            $this->stackManager->deleteStack($stack);
            $output->writeln("Triggered deletion of stack '$stack'.");
        }
    }

}