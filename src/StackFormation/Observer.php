<?php

namespace StackFormation;

use Aws\CloudFormation\Exception\CloudFormationException;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\Table;

class Observer
{

    protected $stack;
    protected $stackFactory;
    protected $output;

    public function __construct(Stack $stack, StackFactory $stackFactory, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $this->stack = $stack;
        $this->stackFactory = $stackFactory;
        $this->output = $output;
    }

    public function deleteOnSignal()
    {
        $terminator = new Terminator($this->stack, $this->output);
        $terminator->setupSignalHandler();
        return $this;
    }

    public function observeStackActivity($pollInterval = 10)
    {
        $returnValue = 0;
        $printedEvents = [];
        $first = true;
        do {
            if ($first) {
                $first = false;
            } else {
                sleep($pollInterval);
            }

            // load fresh instance for updated status
            $this->stack = $this->stackFactory->getStack($this->stack->getName(), true);
            $status = $this->stack->getStatus();

            $this->output->writeln("-> Polling... (Stack Status: $status)");

            $stackGone = false; // while deleting
            try {
                $events = $this->stack->getEvents();

                $logMessages = [];

                $rows = [];
                foreach ($events as $eventId => $event) {
                    if (!in_array($eventId, $printedEvents)) {
                        $printedEvents[] = $eventId;
                        $rows[] = [
                            // $event['Timestamp'],
                            Helper::decorateStatus($event['Status']),
                            $event['ResourceType'],
                            $event['LogicalResourceId'],
                            wordwrap($event['ResourceStatusReason'], 40, "\n"),
                        ];

                        if (preg_match('/See the details in CloudWatch Log Stream: (.*)/', $event['ResourceStatusReason'], $matches)) {
                            $logStream = $matches[1];

                            $logGroupName = Helper::findCloudWatchLogGroupByStream($logStream);

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
                        } elseif (preg_match('/WaitCondition received failed message:.*for uniqueId: (i-[0-9a-f]+)/', $event['ResourceStatusReason'], $matches)) {
                            $instanceId = $matches[1];
                            if (class_exists('\AwsInspector\Model\Ec2\Repository')) {
                                $ec2Repo = new \AwsInspector\Model\Ec2\Repository();
                                $instance = $ec2Repo->findEc2InstanceBy('instance-id', $instanceId);
                                $res = $instance->exec('tail -50 /var/log/cloud-init-output.log');
                                $logMessages = array_merge(
                                    [ "==> Showing last 50 lines in /var/log/cloud-init-output.log"],
                                    $res['output']
                                );
                            }
                        }
                    }
                }

                $table = new Table($this->output);
                $table->setRows($rows);
                $table->render();

                if ($logMessages) {
                    $this->output->writeln('');
                    $this->output->writeln("====================");
                    $this->output->writeln("Detailed log output:");
                    $this->output->writeln("====================");
                    foreach ($logMessages as $line) {
                        $this->output->writeln(trim($line));
                    }
                }
            } catch (CloudFormationException $exception) {
                $message = \StackFormation\Helper::extractMessage($exception);
                if ($message == "Stack [{$this->stack->getName()}] does not exist") {
                    $stackGone = true;
                    $this->output->writeln("-> Stack gone.");
                } else {
                    throw $exception;
                }

            }
        } while (!$stackGone && strpos($status, 'IN_PROGRESS') !== false);

        $formatter = new FormatterHelper();
        if (strpos($status, 'FAILED') !== false) {
            $formattedBlock = $formatter->formatBlock(['Error!', 'Status: ' . $status], 'error', true);
        } else {
            $formattedBlock = $formatter->formatBlock(['Completed', 'Status: ' . $status], 'info', true);
        }

        if (!in_array($status, ['CREATE_COMPLETE', 'UPDATE_COMPLETE'])) {
            $returnValue = 1;
        }

        $this->output->writeln("\n\n$formattedBlock\n\n");

        $this->output->writeln("== OUTPUTS ==");
        try {
            $rows = [];
            foreach ($this->stack->getOutputs() as $key => $value) {
                $value = strlen($value) > 100 ? substr($value, 0, 100) . "..." : $value;
                $rows[] = [$key, $value];
            }

            $table = new Table($this->output);
            $table
                ->setHeaders(['Key', 'Value'])
                ->setRows($rows);
            $table->render();
        } catch (\Exception $e) {
            // never mind...
        }

        return $returnValue;
    }
}
