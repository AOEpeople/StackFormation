<?php

namespace StackFormation\Command\Blueprint\Show;

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

        $changeSetResult = $this->blueprintAction->getChangeSet($blueprint, true);

        $rows = [];
        foreach ($changeSetResult->search('Changes[]') as $change) {
            $resourceChange = $change['ResourceChange'];
            $rows[] = [
                // $change['Type'], // would this ever show anything other than 'Resource'?
                Helper::decorateChangesetAction($resourceChange['Action']),
                $resourceChange['LogicalResourceId'],
                isset($resourceChange['PhysicalResourceId']) ? $resourceChange['PhysicalResourceId'] : '',
                $resourceChange['ResourceType'],
                isset($resourceChange['Replacement']) ? Helper::decorateChangesetReplacement($resourceChange['Replacement']) : '',
            ];
        }

        $table = new Table($output);
        $table
            ->setHeaders(['Action', 'LogicalResourceId', 'PhysicalResourceId', 'ResourceType', 'Replacement'])
            ->setRows($rows);
        $table->render();
    }
}
