<?php

namespace StackFormation\Command;

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

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $stack = $input->getArgument('stack');
        if (empty($stack)) {
            $dialog = $this->getHelper('dialog');
            /* @var $dialog \Symfony\Component\Console\Helper\DialogHelper */
            $stacksFromApi = array_keys($this->stackManager->getStacksFromApi());

            $stack = $dialog->select(
                $output,
                'Please select the stack you want to observe',
                $stacksFromApi
            );
            $input->setArgument('stack', $stacksFromApi[$stack]);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stack = $input->getArgument('stack');
        $this->stackManager->observeStackActivity($stack, $output);
    }

}