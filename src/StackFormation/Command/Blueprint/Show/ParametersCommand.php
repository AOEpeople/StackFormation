<?php

namespace StackFormation\Command\Blueprint\Show;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ParametersCommand extends \StackFormation\Command\AbstractCommand
{

    protected function configure()
    {
        $this
            ->setName('blueprint:show:parameters')
            ->setDescription('Preview parameters and tags')
            ->addArgument(
                'blueprint',
                InputArgument::REQUIRED,
                'Blueprint'
            )
            ->addOption(
                'unresolved',
                null,
                InputOption::VALUE_NONE,
                'Do not resolve placeholders'
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->interactAskForBlueprint($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $blueprint = $this->blueprintFactory->getBlueprint($input->getArgument('blueprint'));
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
