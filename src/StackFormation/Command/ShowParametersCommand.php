<?php

namespace StackFormation\Command;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ShowParametersCommand extends AbstractCommand
{

    protected function configure()
    {
        $this
            ->setName('stack:parameters')
            ->setDescription('Preview parameters')
            ->addArgument(
                'stack',
                InputArgument::REQUIRED,
                'Stack'
            )
            ->addOption(
                'unresolved',
                null,
                InputOption::VALUE_NONE,
                'Do not resolve placeholders'
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->interactAskForConfigStack($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stack = $input->getArgument('stack');
        $unresolved = $input->getOption('unresolved');
        $output->writeln("Stack '$stack':");
        $parameters = $this->stackManager->getParametersFromConfig($stack, !$unresolved);

        $table = new Table($output);
        $table
            ->setHeaders(['Key', 'Value'])
            ->setRows($parameters);
        $table->render();
    }
}
