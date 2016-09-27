<?php

namespace StackFormation\Helper;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class StackEventsTable extends \Symfony\Component\Console\Helper\Table {

    protected $printedEventIds = [];

    /**
     * @var OutputInterface
     */
    protected $localOutput;

    public function __construct(OutputInterface $output)
    {
        parent::__construct($output);
        // the parent class's output is private, so we need a local one here...
        $this->localOutput = $output;
    }

    public function renderEvents(array $events) {
        $detailedLog = [];
        $rows = [];
        foreach ($events as $eventId => $event) {
            if (!in_array($eventId, $this->printedEventIds)) {
                $this->printedEventIds[] = $eventId;
                $rows[] = [
                    Decorator::decorateStatus($event['Status']),
                    $event['ResourceType'],
                    $event['LogicalResourceId'],
                    wordwrap($event['ResourceStatusReason'], 40, "\n"),
                ];
                $detailedLog = $this->getDetailedLogFromResourceStatusReason($event['ResourceStatusReason']) ?: $detailedLog;
            }
        }
        $this->setRows($rows);

        parent::render();
        
        if (count($detailedLog)) {
            $this->printLogMessages($detailedLog);
        }
    }

    /**
     * @param $logMessages
     */
    protected function printLogMessages(array $logMessages)
    {
        if (count($logMessages)) {
            $this->localOutput->writeln('');
            $this->localOutput->writeln("====================");
            $this->localOutput->writeln("Detailed log output:");
            $this->localOutput->writeln("====================");
            foreach ($logMessages as $line) {
                $this->localOutput->writeln(trim($line));
            }
        }
    }

    /**
     * @param $resourceStatusReason
     * @return array
     * @throws \Exception
     */
    public function getDetailedLogFromResourceStatusReason($resourceStatusReason)
    {
        $logMessages = [];
        if (preg_match('/See the details in CloudWatch Log Stream: (.*)/', $resourceStatusReason, $matches)) {
            $logStream = $matches[1];
            $logGroupName = Finder::findCloudWatchLogGroupByStream($logStream);
            if (empty($logGroupName)) {
                throw new \Exception('Could not find logGroupName for logStream: '.$logStream);
            }
            $params = [
                'limit' => 20,
                'logGroupName' => $logGroupName,
                'logStreamName' => $logStream
            ];
            $cloudWatchLogClient = \AwsInspector\SdkFactory::getClient('CloudWatchLogs'); /* @var $cloudWatchLogClient \Aws\CloudWatchLogs\CloudWatchLogsClient */
            $res = $cloudWatchLogClient->getLogEvents($params);
            $logMessages = array_merge(
                [ "==> Showing last 20 messages from $logGroupName -> $logStream" ],
                $res->search('events[].message')
            );
        } elseif (preg_match('/WaitCondition received failed message:.*for uniqueId: (i-[0-9a-f]+)/', $resourceStatusReason, $matches)) {
            $instanceId = $matches[1];
            $ec2Repo = new \AwsInspector\Model\Ec2\Repository();
            $instance = $ec2Repo->findEc2InstanceBy('instance-id', $instanceId);
            if ($instance) {
                try {
                    $res = $instance->exec('tail -50 /var/log/cloud-init-output.log');
                    $logMessages = array_merge(
                        ["==> Showing last 50 lines in /var/log/cloud-init-output.log"],
                        $res['output']
                    );
                } catch (FileNotFoundException $e) {
                    $logMessages = ["Could not log in to instance '$instanceId' because the pem file could not be found"];
                }
            } else {
                $logMessages = ["Could not find instance '$instanceId'"];
            }
        }
        return $logMessages;
    }

}
