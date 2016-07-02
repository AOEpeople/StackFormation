<?php

namespace StackFormation\Command\Blueprint\Show;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ChangesetCommand extends \StackFormation\Command\Blueprint\AbstractBlueprintCommand
{

    protected function configure()
    {
        $this
            ->setName('blueprint:show:changeset')
            ->setDescription('Preview changeset');
    }

    protected function executeWithBlueprint(\StackFormation\Blueprint $blueprint, InputInterface $input, OutputInterface $output)
    {
        $blueprintAction = new \StackFormation\BlueprintAction($blueprint, $this->profileManager, $output);
        $changeSetResult = $blueprintAction->getChangeSet();
        $table = new \StackFormation\Helper\ChangeSetTable($output);
        $table->render($changeSetResult);
    }
}
