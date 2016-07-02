<?php

namespace StackFormation\Command\Blueprint;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractBlueprintCommand extends \StackFormation\Command\AbstractCommand
{

    public function __construct($name = null)
    {
        parent::__construct($name);
        $this->addArgument('blueprint', InputArgument::REQUIRED, 'Blueprint');
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->interactAskForBlueprint($input, $output);
    }

    abstract protected function executeWithBlueprint(\StackFormation\Blueprint $blueprint, InputInterface $input, OutputInterface $output);

    protected final function execute(InputInterface $input, OutputInterface $output)
    {
        $blueprintName = $input->getArgument('blueprint');
        $blueprint = $this->blueprintFactory->getBlueprint($blueprintName);
        return $this->executeWithBlueprint($blueprint, $input, $output);
    }
}
