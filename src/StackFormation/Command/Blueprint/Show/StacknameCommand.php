<?php

namespace StackFormation\Command\Blueprint\Show;

use StackFormation\Blueprint;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StacknameCommand extends \StackFormation\Command\Blueprint\AbstractBlueprintCommand
{

    protected function configure()
    {
        $this
            ->setName('blueprint:show:stackname')
            ->setDescription('Return stack name for given blueprint name (resolving placeholders)');
    }

    protected function executeWithBlueprint(Blueprint $blueprint, InputInterface $input, OutputInterface $output)
    {
        $output->writeln($blueprint->getStackName());
    }
}
