<?php

namespace StackFormation\Command\Stack;

use StackFormation\Diff;
use StackFormation\Helper;
use StackFormation\Helper\Validator;
use StackFormation\Stack;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DiffCommand extends \StackFormation\Command\Stack\AbstractStackCommand
{

    protected function configure()
    {
        $this
            ->setName('stack:diff')
            ->setDescription('Compare a stack\'s template and input parameters with its blueprint');
    }

    protected function executeWithStack(Stack $stack, InputInterface $input, OutputInterface $output)
    {
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
