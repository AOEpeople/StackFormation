<?php

namespace StackFormation\Command\Stack;

use StackFormation\Stack;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DependencyGraphCommand extends \StackFormation\Command\AbstractCommand
{

    protected function configure()
    {
        $this
            ->setName('stack:dependency-graph')
            ->setDescription('Create depenency graph')
            ->addOption(
                'nameFilter',
                null,
                InputOption::VALUE_OPTIONAL,
                'Name Filter (regex). Example --nameFilter \'/^foo/\''
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $nameFilter = $input->getOption('nameFilter');
        $stacks = $this->getStackFactory()->getStacksFromApi(false, $nameFilter);

        foreach ($stacks as $stackName => $stack) { /* @var $stack Stack */
            echo "$stackName -> {$stack->getBlueprintName()}\n";
        }


    }

}
