<?php

namespace StackFormation\Command;

use StackFormation\StackManager;
use StackFormation\Command\AbstractCommand;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends AbstractCommand
{

    protected function configure()
    {
        $this
            ->setName('stack:list')
            ->setDescription('List Stacks');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stacks = $this->stackManager->getStacksFromApi();

        $rows=[];
        foreach($stacks as $stackName => $details) {
            $rows[] = [$stackName, $details['Status']];
        }

        $table = $this->getHelper('table');
        $table
            ->setHeaders(array('Name', 'Status'))
            ->setRows($rows)
        ;
        $table->render($output);
    }

}