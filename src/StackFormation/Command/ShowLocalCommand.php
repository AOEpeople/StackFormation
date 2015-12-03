<?php

namespace StackFormation\Command;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ShowLocalCommand extends AbstractCommand
{

    protected function configure()
    {
        $this
            ->setName('stack:show-local')
            ->setDescription('Show parameters from local configuration (resolving \'output:*:*\', \'resource:*:*\' and \'env:*\')')
            ->addArgument(
                'stack',
                InputArgument::REQUIRED,
                'Stack'
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->interact_askForConfigStack($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stack = $input->getArgument('stack');
        $output->writeln("Stack '$stack':");

        $parameters = $this->stackManager->getParametersFromConfig($stack);

        $table = new Table($output);
        $table
            ->setHeaders(['Key', 'Value'])
            ->setRows($parameters);
        $table->render();
    }
}
