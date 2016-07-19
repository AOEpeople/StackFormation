<?php

namespace AwsInspector\Command\CloudwatchLogs;

use AwsInspector\Model\CloudWatchLogs\LogGroup;
use AwsInspector\Model\CloudWatchLogs\Repository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteLogGroupCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('cloudwatchlogs:delete-log-group')
            ->setDescription('Delete log group')
            ->addArgument(
                'group',
                InputArgument::REQUIRED,
                'Log group name pattern'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $groupPattern = $input->getArgument('group');
        $repository = new Repository();
        foreach ($repository->findLogGroups($groupPattern) as $logGroup) { /* @var $logGroup LogGroup */
            $output->writeln('Deleting ' . $logGroup->getLogGroupName());
            $repository->deleteLogGroup($logGroup->getLogGroupName());
        }
    }

}
