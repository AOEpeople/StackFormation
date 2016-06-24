<?php

namespace StackFormation\Command\Blueprint\Show;

use StackFormation\BlueprintAction;
use StackFormation\Helper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ChangesetCommand extends \StackFormation\Command\AbstractCommand
{

    protected function configure()
    {
        $this
            ->setName('blueprint:show:changeset')
            ->setDescription('Preview changeset')
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
        $blueprint = $this->blueprintFactory->getBlueprint($input->getArgument('blueprint'));
        $blueprintAction = new BlueprintAction($blueprint, $this->profileManager, $this->stackFactory, $output);
        $changeSetResult = $blueprintAction->getChangeSet();
        $table = new Helper\ChangeSetTable($output);
        $table->render($changeSetResult);
    }
}
