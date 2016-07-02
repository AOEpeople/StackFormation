<?php

namespace StackFormation\Command\Blueprint\Show;

use StackFormation\Blueprint;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TemplateCommand extends \StackFormation\Command\Blueprint\AbstractBlueprintCommand
{

    protected function configure()
    {
        $this
            ->setName('blueprint:show:template')
            ->setDescription('Preview preprocessed template');
    }

    protected function executeWithBlueprint(Blueprint $blueprint, InputInterface $input, OutputInterface $output)
    {
        $output->writeln($blueprint->getPreprocessedTemplate());
    }
}
