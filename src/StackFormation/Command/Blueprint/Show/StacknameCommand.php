<?php

namespace StackFormation\Command\Blueprint\Show;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StacknameCommand extends \StackFormation\Command\AbstractCommand
{

    protected function configure()
    {
        $this
            ->setName('blueprint:show:stackname')
            ->setDescription('Return stack name for given blueprint name (resolving placeholders)')
            ->addArgument(
                'blueprint',
                InputArgument::REQUIRED,
                'Blueprint'
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->interactAskForBlueprint($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $blueprint = $input->getArgument('blueprint');
        $stackName = $this->stackManager->getConfig()->getEffectiveStackName($blueprint);
        $output->writeln($stackName);
    }
}
