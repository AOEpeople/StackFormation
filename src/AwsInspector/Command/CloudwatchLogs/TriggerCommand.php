<?php

namespace AwsInspector\Command\CloudwatchLogs;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TriggerCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('cloudwatchlogs:add-trigger')
            ->setDescription('Creates or updates a subscription filter and associates it with the specified log group')
            ->addArgument(
                'group',
                InputArgument::REQUIRED,
                'Log group name pattern'
            )->addArgument(
                'destinationArn',
                InputArgument::REQUIRED,
                'The ARN of the destination to deliver matching log events to (Lambda ARN for example)'
            )->addArgument(
                'filterName',
                InputArgument::REQUIRED,
                'A name for the subscription filter'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // TODO: refactor this to use \AwsInspector\Model\CloudWatchLogs\Repository

        $groupPattern = $input->getArgument('group');
        $destinationArn = $input->getArgument('destinationArn');
        $filterName = $input->getArgument('filterName');

        /* @var $cloudwatchLogsClient \Aws\CloudWatchLogs\CloudWatchLogsClient */
        $cloudwatchLogsClient = \AwsInspector\SdkFactory::getClient('cloudwatchlogs');

        $nextToken = null;
        $logsWithLimitExceededException = [];

        do {
            $params = ['limit' => 50];
            if ($nextToken) {
                $params['nextToken'] = $nextToken;
            }

            $result = $cloudwatchLogsClient->describeLogGroups($params);
            foreach ($result->get('logGroups') as $logGroup) {
                $name = $logGroup['logGroupName'];
                if (preg_match('/'.$groupPattern.'/', $name)) {
                    try {
                        $cloudwatchLogsClient->putSubscriptionFilter([
                            'destinationArn' => $destinationArn,
                            'filterName' => $filterName,
                            'filterPattern' => '',
                            'logGroupName' => $logGroup['logGroupName']
                        ]);
                    } catch (\Aws\CloudWatchLogs\Exception\CloudWatchLogsException $e) {
                        if ($e->getAwsErrorCode() == 'LimitExceededException') {
                            $logsWithLimitExceededException[] = $logGroup;
                        }
                    }

                    $output->writeln('Add trigger for ' . $logGroup['logGroupName']);
                }
            }
            $nextToken = $result->get("nextToken");
        } while ($nextToken);

        if (!empty($logsWithLimitExceededException)) {
            $output->writeln('The following log groups has already a different subscription:');
            foreach ($logsWithLimitExceededException as $logGroup) {
                $output->writeln("\t" . $logGroup['logGroupName']);
            }
        }
    }
}
