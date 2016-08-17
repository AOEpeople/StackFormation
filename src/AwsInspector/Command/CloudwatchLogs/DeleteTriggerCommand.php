<?php

namespace AwsInspector\Command\CloudwatchLogs;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteTriggerCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('cloudwatchlogs:delete-trigger')
            ->setDescription('Deletes a subscription')
            ->addArgument(
                'group',
                InputArgument::REQUIRED,
                'Log group name pattern'
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
        $filterName = $input->getArgument('filterName');

        /* @var $cloudwatchLogsClient \Aws\CloudWatchLogs\CloudWatchLogsClient */
        $cloudwatchLogsClient = \AwsInspector\SdkFactory::getClient('cloudwatchlogs');

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
                    try {
                        $subscriptionFilters = $cloudwatchLogsClient->describeSubscriptionFilters(['logGroupName' => $logGroup['logGroupName']]);
                        if (empty($subscriptionFilters->get('subscriptionFilters'))) {
                            continue;
                        }

                        $cloudwatchLogsClient->deleteSubscriptionFilter([
                            'filterName' => $filterName,
                            'logGroupName' => $logGroup['logGroupName']
                        ]);
                        $output->writeln('Delete trigger for ' . $logGroup['logGroupName']);
                    } catch (\Aws\CloudWatchLogs\Exception\CloudWatchLogsException $e) {
                        if ($e->getAwsErrorCode() != 'ResourceNotFoundException') {
                            throw $e;
                        }
                    }
                }
            }
            $nextToken = $result->get("nextToken");
        } while ($nextToken);
    }
}
