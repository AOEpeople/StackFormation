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
            ->setDescription('Show dependencies to other stacks and environment variables')
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
        $blueprint = $input->getArgument('blueprint');

        $this->stackManager->getBlueprintParameters($blueprint);
        $this->stackManager->getPreprocessedTemplate($blueprint);

        $table = new Table($output);
        $table->setHeaders(['Type', 'Stack', 'Resource', 'Count'])
            ->setRows($this->stackManager->getDependencyTracker()->getStackDependenciesAsFlatList())
            ->render();

        $table = new Table($output);
        $table->setHeaders(['Type', 'Var', 'Count'])
            ->setRows($this->stackManager->getDependencyTracker()->getEnvDependenciesAsFlatList())
            ->render();
        // var_dump($this->stackManager->getDependencyTracker()->getStacks());
    }
}
