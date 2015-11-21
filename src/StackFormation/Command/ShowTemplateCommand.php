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

class ShowTemplateCommand extends AbstractCommand
{

    protected function configure()
    {
        $this
            ->setName('stack:show-template')
            ->setDescription('Show Preprocessed Template')
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
            $stacksFromConfig = $this->config->getStacknames();

            $stack = $dialog->select(
                $output,
                'Please select the stack you want to deploy',
                $stacksFromConfig
            );
            $input->setArgument('stack', $stacksFromConfig[$stack]);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stack = $input->getArgument('stack');
        $output->writeln("Stack '$stack':");
        $output->writeln($this->stackManager->getPreprocessedTemplate($stack));
    }

}
