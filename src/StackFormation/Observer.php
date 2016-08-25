<?php

namespace StackFormation;

use Aws\CloudFormation\Exception\CloudFormationException;
use StackFormation\Exception\StackNotFoundException;
use StackFormation\Helper\Exception;
use StackFormation\Helper\StackEventsTable;
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

    public function observeStackActivity($pollInterval = 20)
    {
        $eventTable = new StackEventsTable($this->output);
        $lastStatus = Poller::poll(function() use ($eventTable) {
            try {
                try {
                    $this->stack = $this->stackFactory->getStack($this->stack->getName(), true); // load fresh instance for updated status
                    $this->output->writeln("-> Polling... (Stack Status: {$this->stack->getStatus()})");
                    $eventTable->renderEvents($this->stack->getEvents());
                } catch (CloudFormationException $exception) {
                    throw Exception::refineException($exception);
                }
            } catch (StackNotFoundException $exception) {
                $this->output->writeln("-> Stack gone.");
                return Stack::STATUS_STACK_GONE; // this is != false and will stop the poller
            }
            return $this->stack->isInProgress() ? false : $this->stack->getStatus();
        }, $pollInterval, 1000);

        $this->printStatus($lastStatus);
        $this->printResources();
        $this->printOutputs();
        return $lastStatus;
    }

    public function isSuccessfulStatus($status)
    {
        return in_array($status, ['CREATE_COMPLETE', 'UPDATE_COMPLETE', Stack::STATUS_STACK_GONE]);
    }

    protected function printResources()
    {
        $this->output->writeln("== RESOURCES ==");
        try {
            $rows = [];
            foreach ($this->stack->getResources() as $key => $value) {
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
    }

    protected function printOutputs()
    {
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
    }

    /**
     * @param $lastStatus
     */
    protected function printStatus($lastStatus)
    {
        $formatter = new FormatterHelper();
        $formattedBlock = $this->isSuccessfulStatus($lastStatus)
            ? $formatter->formatBlock(['Completed', 'Last Status: ' . $lastStatus], 'info', true)
            : $formatter->formatBlock(['Error!', 'Last Status: ' . $lastStatus], 'error', true);
        $this->output->writeln("\n\n$formattedBlock\n\n");
    }
}
