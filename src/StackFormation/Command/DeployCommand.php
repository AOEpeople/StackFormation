<?php

namespace StackFormation\Command;

use StackFormation\Config;
use StackFormation\Command\AbstractCommand;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
        try {
            $this->stackManager->deployStack($stack, 'DO_NOTHING'); // TODO: expose to option

            $stack = $this->stackManager->getConfig()->getEffectiveStackName($stack);

            $output->writeln("Triggered deployment of stack '$stack'.");
            $output->writeln("Run this if you want to observe the stack creation/update:");
            $output->writeln("{$GLOBALS['argv'][0]} stack:observe $stack");
        } catch (\Aws\CloudFormation\Exception\CloudFormationException $exception) {
            if (strpos($exception->getMessage(), 'No updates are to be performed.') !== false) {
                $output->writeln("No updates are to be performed for stack '$stack'");
            } else {
                throw $exception;
            }
        }
    }

}
