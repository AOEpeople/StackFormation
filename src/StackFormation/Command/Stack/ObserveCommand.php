<?php

namespace StackFormation\Command\Stack;

use StackFormation\Helper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ObserveCommand extends \StackFormation\Command\AbstractCommand
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
            )
            ->addOption(
                'deleteOnTerminate',
                null,
                InputOption::VALUE_NONE,
                'Delete current stack if StackFormation received SIGTERM (e.g. Jenkins job abort) or SIGINT (e.g. CTRL+C)'
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->interactAskForStack($input, $output, null, '/IN_PROGRESS/');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stack = $this->getStackFactory()->getStack($input->getArgument('stack'));
        Helper::validateStackname($stack);

        $deleteOnTerminate = $input->getOption('deleteOnTerminate');
        return $stack->observe($output, $this->getStackFactory(), $deleteOnTerminate);
    }
}
