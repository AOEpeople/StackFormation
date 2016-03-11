<?php

namespace StackFormation\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ShowTemplateCommand extends AbstractCommand
{

    protected function configure()
    {
        $this
            ->setName('stack:template')
            ->setDescription('Preview preprocessed local template')
            ->addArgument(
                'stack',
                InputArgument::REQUIRED,
                'Stack'
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->interactAskForTemplate($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stack = $input->getArgument('stack');
        $output->writeln($this->stackManager->getPreprocessedTemplate($stack));
    }
}
