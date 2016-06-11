<?php

namespace StackFormation\Command\Stack\Show;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DependantsCommand extends \StackFormation\Command\AbstractCommand
{

    protected function configure()
    {
        $this
            ->setName('stack:show:dependants')
            ->setDescription('Show (outgoing) dependencies to blueprints')
            ->addArgument(
                'stack',
                InputArgument::REQUIRED,
                'Stack'
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->interactAskForStack($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stack = $this->stackFactory->getStack($input->getArgument('stack'));

        $this->dependencyTracker->reset();
        foreach ($this->blueprintFactory->getAllBlueprints() as $blueprint) {
            $blueprint->gatherDependencies();
        }

        $dependants = $this->dependencyTracker->findDependantsForStack($stack->getName());

        $rows = [];
        foreach ($dependants as $dependant) {
            $rows[] = [
                $dependant['targetType'] . ':' . $dependant['targetResource'],
                $dependant['type'] . ':' . $dependant['blueprint'] . ':' . $dependant['key'],
            ];
        }

        $output->writeln("Following blueprints depend on stack '{$stack->getName()}:");

        $table = new Table($output);
        $table->setHeaders(['Origin (Stack: '.$stack->getName() . ')', 'Blueprint'])
            ->setRows($rows)
            ->render();
    }
}
