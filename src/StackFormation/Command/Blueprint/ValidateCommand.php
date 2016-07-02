<?php

namespace StackFormation\Command\Blueprint;

use StackFormation\Blueprint;
use StackFormation\BlueprintAction;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ValidateCommand extends \StackFormation\Command\Blueprint\AbstractBlueprintCommand
{

    protected function configure()
    {
        $this
            ->setName('blueprint:validate')
            ->setDescription('Validate a blueprint\'s template');
    }

    protected function executeWithBlueprint(Blueprint $blueprint, InputInterface $input, OutputInterface $output)
    {
        $blueprintAction = new BlueprintAction($blueprint, $this->profileManager, $output);
        $blueprintAction->validateTemplate(); // will throw an exception if there's a problem

        $formatter = new FormatterHelper();
        $formattedBlock = $formatter->formatBlock(['No validation errors found.'], 'info', true);

        $output->writeln("\n\n$formattedBlock\n\n");
    }
}
