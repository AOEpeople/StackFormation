<?php

namespace StackFormation\Command\Stack;

use StackFormation\Helper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class DeleteCommand extends \StackFormation\Command\AbstractCommand
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
        $this->interactAskForStack($input, $output);

        if (!$input->getOption('force')) {
            $stacks = $this->getResolvedStacks($input);
            $stacks = "\n - " . implode("\n - ", $stacks) . "\n";
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion("Are you sure you want to delete following stacks? $stacks [y/N] ", false);
            if (!$helper->ask($input, $output, $question)) {
                throw new \Exception('Operation aborted');
            }
            $input->setOption('force', true);
        }
    }

    protected function getResolvedStacks(InputInterface $input)
    {
        $helper = new Helper();
        $stacks = $helper->find(
            (array)$input->getArgument('stack'),
            $this->getStacks()
        );

        $except = $input->getOption('except');
        if (!empty($except)) {
            if (($key = array_search($except, $stacks)) !== false) {
                unset($stacks[$key]);
            }
        }

        return $stacks;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('force')) {
            throw new \Exception('Operation aborted (use --force)');
        }

        $stacks = $this->getResolvedStacks($input);

        if (count($stacks) == 0) {
            $output->writeln("No stacks deleted.");
        }

        foreach ($stacks as $stackName) {
            $this->stackFactory->getStack($stackName)->delete();
            $output->writeln("Triggered deletion of stack '$stackName'.");
        }
    }
}
