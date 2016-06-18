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

class SetRetentionCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('cloudwatchlogs:set-retention')
            ->setDescription('Set retention')
            ->addArgument(
                'days',
                InputArgument::REQUIRED,
                'retention in days'
            )
            ->addArgument(
                'group',
                InputArgument::REQUIRED,
                'Log group name pattern'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $days = $input->getArgument('days');
        $days = intval($days);
        if ($days == 0) {
            throw new \Exception('Invalid retention period');
        }
        $groupPattern = $input->getArgument('group');

        $cloudwatchLogsClient = \AwsInspector\SdkFactory::getClient('cloudwatchlogs'); /* @var $cloudwatchLogsClient \Aws\CloudWatchLogs\CloudWatchLogsClient */

        $totalBytes = 0;
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
                    $retention = isset($logGroup['retentionInDays']) ? $logGroup['retentionInDays'] : 'never';
                    if ($retention != $days) {
                        $output->writeln('Updating ' . $logGroup['logGroupName']);
                        $cloudwatchLogsClient->putRetentionPolicy([
                            'logGroupName' => $name,
                            'retentionInDays' => $days
                        ]);
                    } else {
                        $output->writeln('Skipping ' . $logGroup['logGroupName']);
                    }
                } else {
                    $output->writeln('Does not match pattern: ' . $logGroup['logGroupName']);
                }
            }
            $nextToken = $result->get("nextToken");
        } while ($nextToken);
    }

}