<?php

namespace StackFormation\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeployCommand extends AbstractCommand
{

    protected function configure()
    {
        $this
            ->setName('stack:deploy')
            ->setDescription('Deploy Stack')
            ->addArgument(
                'stack',
                InputArgument::REQUIRED,
                'Stack'
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->interact_askForConfigStack($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stack = $input->getArgument('stack');
        $this->stackManager->deployStack($stack, 'DO_NOTHING'); // TODO: expose to option

        $effectiveStackName = $this->stackManager->getConfig()->getEffectiveStackName($stack);

        $output->writeln("Triggered deployment of stack '$effectiveStackName'.");
        $output->writeln("Run this if you want to observe the stack creation/update:");
        $output->writeln("{$GLOBALS['argv'][0]} stack:observe $effectiveStackName");
    }

}
