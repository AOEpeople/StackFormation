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

class ShowOutputsCommand extends AbstractCommand
{

    protected function configure()
    {
        $this
            ->setName('stack:show-outputs')
            ->setDescription('Show Outputs from online stacks')
            ->addArgument(
                'stack',
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
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
                'Please select a stack',
                $stacksFromApi
            );
            $input->setArgument('stack', [$stacksFromApi[$stack]]);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stacks = $input->getArgument('stack');
        foreach ($stacks as $stack) {
            $output->writeln("Stack '$stack':");

            $outputs = $this->stackManager->getOutputs($stack);

            $rows = [];

            foreach ($outputs as $key => $value) {
                $rows[] = [$key, $value];
            }

            $table = $this->getHelper('table');
            $table
                ->setHeaders(array('Key', 'Value'))
                ->setRows($rows)
            ;
            $table->render($output);

        }
    }
}
