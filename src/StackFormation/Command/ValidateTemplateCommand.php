<?php

namespace StackFormation\Command;

use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ValidateTemplateCommand extends AbstractCommand
{

    protected function configure()
    {
        $this
            ->setName('stack:validate')
            ->setDescription('Validate template')
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
        $this->stackManager->validateTemplate($stack);
        // will throw an exception if there's a problem

        $formatter = new FormatterHelper();
        $formattedBlock = $formatter->formatBlock(['No validation errors found.'], 'info', true);

        $output->writeln("\n\n$formattedBlock\n\n");
    }
}
