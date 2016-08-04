<?php

namespace StackFormation\Command\Stack\Show;

use StackFormation\Helper;
use StackFormation\Helper\Validator;
use StackFormation\Stack;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DependantsCommand extends \StackFormation\Command\Stack\AbstractStackCommand
{

    protected function configure()
    {
        $this
            ->setName('stack:show:dependants')
            ->setDescription('Show (outgoing) dependencies to blueprints');
    }

    protected function executeWithStack(Stack $stack, InputInterface $input, OutputInterface $output)
    {
        $this->dependencyTracker->reset();
        foreach ($this->blueprintFactory->getAllBlueprints() as $blueprint) {
            $blueprint->getPreprocessedTemplate();
        }

        $dependants = $this->dependencyTracker->findDependantsForStack($stack->getName());

        $rows = [];
        foreach ($dependants as $dependant) {
            $rows[] = [
                $dependant['targetType'] . ':' . $dependant['targetResource'],
                $dependant['type'] . ':' . $dependant['blueprint'] . ':' . $dependant['key'],
            ];
        }

        $output->writeln("Following blueprints depend on stack '{$stack->getName()}':");

        $table = new Table($output);
        $table->setHeaders(['Origin (Stack: '.$stack->getName() . ')', 'Blueprint'])
            ->setRows($rows)
            ->render();
    }
}
