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

class ShowParametersCommand extends AbstractCommand
{

    protected function configure()
    {
        $this
            ->setName('stack:show-parameters')
            ->setDescription('Show Stack Parameters from local configuration (resolving \'output:*:*\' and \'resource:*:*\')')
            ->addArgument(
                'stack',
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'Stack'
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $stacks = $input->getArgument('stack');
        if (count($stacks) == 0) {
            $dialog = $this->getHelper('dialog');
            /* @var $dialog \Symfony\Component\Console\Helper\DialogHelper */
            $stacksFromConfig = $this->config->getStacknames();

            $stack = $dialog->select(
                $output,
                'Please select the stack you want to deploy',
                $stacksFromConfig
            );
            $input->setArgument('stack', [$stacksFromConfig[$stack]]);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stacks = $input->getArgument('stack');
        foreach ($stacks as $stack) {
            $output->writeln("Stack '$stack':");

            $parameters = $this->stackManager->getParametersFromConfig($stack);

            $table = $this->getHelper('table');
            $table
                ->setHeaders(array('Key', 'Value'))
                ->setRows($parameters)
            ;
            $table->render($output);

        }
    }

}
