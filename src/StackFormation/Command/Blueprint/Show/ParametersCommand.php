<?php

namespace StackFormation\Command\Blueprint\Show;

use StackFormation\Blueprint;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ParametersCommand extends \StackFormation\Command\Blueprint\AbstractBlueprintCommand
{

    protected function configure()
    {
        $this
            ->setName('blueprint:show:parameters')
            ->setDescription('Preview parameters and tags')
            ->addOption(
                'unresolved',
                null,
                InputOption::VALUE_NONE,
                'Do not resolve placeholders'
            );
    }

    protected function executeWithBlueprint(Blueprint $blueprint, InputInterface $input, OutputInterface $output)
    {
        $unresolved = $input->getOption('unresolved');

        $output->writeln("Blueprint '{$blueprint->getName()}':");

        $parameters = $blueprint->getParameters(!$unresolved);

        $output->writeln('== PARAMETERS ==');
        $table = new Table($output);
        $table
            ->setHeaders(['Key', 'Value'])
            ->setRows($parameters);
        $table->render();

        $output->writeln('== TAGS ==');
        $table = new Table($output);
        $table
            ->setHeaders(['Key', 'Value'])
            ->setRows($blueprint->getTags(!$unresolved));
        $table->render();
    }
}
