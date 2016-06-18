<?php

namespace AwsInspector\Command\CloudwatchLogs;

use AwsInspector\Finder;
use AwsInspector\Model\Ec2\Instance;
use AwsInspector\Model\Ec2\Repository;
use AwsInspector\Ssh\Agent;
use AwsInspector\Ssh\Identity;
use AwsInspector\Ssh\PrivateKey;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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

        $cloudwatchLogsClient = \AwsInspector\SdkFactory::getClient('cloudwatchlogs'); /* @var $cloudwatchLogsClient \Aws\CloudWatchLogs\CloudWatchLogsClient */

        $nextToken = null;
        do {
            $params = ['limit' => 50];
            if ($nextToken) {
                $params['nextToken'] = $nextToken;
            }
            $result = $cloudwatchLogsClient->describeLogGroups($params);
            foreach ($result->get('logGroups') as $logGroup) {
                $name = $logGroup['logGroupName'];
                if (preg_match('/'.$groupPattern.'/', $name)) {
                    $output->writeln('Deleting ' . $logGroup['logGroupName']);
                    $cloudwatchLogsClient->deleteLogGroup([
                        'logGroupName' => $name
                    ]);
                } else {
                    $output->writeln('Does not match pattern: ' . $logGroup['logGroupName']);
                }
            }
            $nextToken = $result->get("nextToken");
        } while ($nextToken);
    }

}