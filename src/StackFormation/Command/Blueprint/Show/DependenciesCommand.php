<?php

namespace StackFormation\Command\Blueprint\Show;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DependenciesCommand extends \StackFormation\Command\AbstractCommand
{

    protected function configure()
    {
        $this
            ->setName('blueprint:show:dependencies')
            ->setDescription('Show (incoming) dependencies to stacks and environment variables')
            ->addArgument(
                'blueprint',
                InputArgument::REQUIRED,
                'Blueprint'
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->interactAskForBlueprint($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $blueprint = $this->blueprintFactory->getBlueprint($input->getArgument('blueprint'));

        // trigger resolving all placeholders
        $this->dependencyTracker->reset();
        $blueprint->gatherDependencies();

        $output->writeln("Blueprint '{$blueprint->getName()} depends on following stack's resources/parameters/outputs:");

        $table = new Table($output);
        $table->setHeaders(['Origin ('.$blueprint->getName().')', 'Source Stack', 'Field'])
            ->setRows($this->dependencyTracker->getStackDependenciesAsFlatList())
            ->render();

        $output->writeln("Blueprint '{$blueprint->getName()} depends on following environment variables:");
        $table = new Table($output);
        $table->setHeaders(['Origin ('.$blueprint->getName().')', 'Type', 'Var', 'Current Value'])
            ->setRows($this->dependencyTracker->getEnvDependenciesAsFlatList())
            ->render();
    }
}
