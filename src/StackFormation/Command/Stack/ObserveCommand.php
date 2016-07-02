<?php

namespace StackFormation\Command\Stack;

use StackFormation\Helper;
use StackFormation\Observer;
use StackFormation\Stack;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ObserveCommand extends \StackFormation\Command\Stack\AbstractStackCommand
{

    protected function configure()
    {
        $this
            ->setName('stack:observe')
            ->setDescription('Observe stack progress')
            ->addOption(
                'deleteOnTerminate',
                null,
                InputOption::VALUE_NONE,
                'Delete current stack if StackFormation received SIGTERM (e.g. Jenkins job abort) or SIGINT (e.g. CTRL+C)'
            );
    }

    protected function executeWithStack(Stack $stack, InputInterface $input, OutputInterface $output)
    {
        $observer = new Observer($stack, $this->getStackFactory(), $output);
        if ($input->getOption('deleteOnTerminate')) {
            $observer->deleteOnSignal();
        }
        return $observer->observeStackActivity();
    }
}
