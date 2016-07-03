<?php

namespace StackFormation\Command\Stack;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractStackCommand extends \StackFormation\Command\AbstractCommand
{

    public function __construct($name = null)
    {
        parent::__construct($name);
        $this->addArgument('stack', InputArgument::REQUIRED, 'Stack');
        $this->afterConfigure();
    }

    protected function afterConfigure()
    {
        // overwrite this in your inheriting class (e.g. for adding optional arguments)
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->interactAskForStack($input, $output);
    }

    abstract protected function executeWithStack(\StackFormation\Stack $stack, InputInterface $input, OutputInterface $output);

    protected final function execute(InputInterface $input, OutputInterface $output)
    {
        $stackName = $input->getArgument('stack');
        Validator::validateStackname($stackName);
        $stack = $this->getStackFactory()->getStack($stackName);
        return $this->executeWithStack($stack, $input, $output);
    }
}
