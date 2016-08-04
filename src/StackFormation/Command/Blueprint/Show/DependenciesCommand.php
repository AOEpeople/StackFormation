<?php

namespace StackFormation\Command\Blueprint\Show;

use StackFormation\Blueprint;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DependenciesCommand extends \StackFormation\Command\Blueprint\AbstractBlueprintCommand
{

    protected function configure()
    {
        $this
            ->setName('blueprint:show:dependencies')
            ->setDescription('Show (incoming) dependencies to stacks and environment variables');
    }

    protected function executeWithBlueprint(Blueprint $blueprint, InputInterface $input, OutputInterface $output)
    {
        // trigger resolving all placeholders
        $this->dependencyTracker->reset();
        $blueprint->getPreprocessedTemplate();

        $output->writeln("Blueprint '{$blueprint->getName()} depends on following stack's resources/parameters/outputs:");

        $table = new Table($output);
        $table->setHeaders(['Origin ('.$blueprint->getName().')', 'Source Stack', 'Field'])
            ->setRows($this->dependencyTracker->getStackDependenciesAsFlatList())
            ->render();

        $output->writeln("Blueprint '{$blueprint->getName()} depends on following environment variables:");
        $table = new Table($output);
        $table->setHeaders(['Var', 'Current Value', 'Type', 'Origin (within "'.$blueprint->getName().'")'])
            ->setRows($this->dependencyTracker->getEnvDependenciesAsFlatList())
            ->render();
    }
}
