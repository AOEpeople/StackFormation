<?php

namespace StackFormation\Command\Stack;

use StackFormation\Diff;
use StackFormation\Helper;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DiffCommand extends \StackFormation\Command\AbstractCommand
{

    protected function configure()
    {
        $this
            ->setName('stack:diff')
            ->setDescription('Compare a stack\'s template and input parameters with its blueprint')
            ->addArgument(
                'stack',
                InputArgument::REQUIRED,
                'Stack name'
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->interactAskForStack($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stack = $this->getStackFactory()->getStack($input->getArgument('stack'));
        Helper::validateStackname($stack->getName());

        $blueprint = $this->blueprintFactory->getBlueprintByStack($stack);

        $diff = new Diff($output);
        $diff->setStack($stack)->setBlueprint($blueprint);

        $formatter = new FormatterHelper();
        $output->writeln("\n" . $formatter->formatBlock(['Parameters:'], 'error', true) . "\n");
        $diff->diffParameters();

        $output->writeln("\n" . $formatter->formatBlock(['Template:'], 'error', true) . "\n");
        $diff->diffTemplates();
    }

}
