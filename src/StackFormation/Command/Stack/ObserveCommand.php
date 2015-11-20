<?php

namespace StackFormation\Command\Stack;

use StackFormation\StackManager;
use StackFormation\Command\AbstractCommand;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ObserveCommand extends AbstractCommand
{

    protected function configure()
    {
        $this
            ->setName('stack:observe')
            ->setDescription('Observe stack progress')
            ->addArgument(
                'stack',
                InputArgument::REQUIRED,
                'Stack'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stack = $input->getArgument('stack');
        $this->stackManager->observeStackActivity($stack, $output);
    }

}