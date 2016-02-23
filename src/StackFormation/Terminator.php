<?php

namespace StackFormation;

class Terminator
{
    protected $currentStackName;
    protected $stackManager;
    protected $output;

    public function __construct($currentStackName, \StackFormation\StackManager $stackManager, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $this->currentStackName = $currentStackName;
        $this->stackManager = $stackManager;
        $this->output = $output;
    }

    public function setupSignalHandler()
    {
        $this->output->writeln('Handling signals SIGTERM and SIGINT for stack ' . $this->currentStackName);
        declare(ticks = 1);
        pcntl_signal(SIGTERM, array($this, 'signalHandler')); // Jenkins: aborting a job
        pcntl_signal(SIGINT, array($this, 'signalHandler')); // CTRL+C on command line
    }

    public function signalHandler($signo)
    {
        switch ($signo) {
            case SIGINT: $this->output->writeln("Caught SIGINT"); break;
            case SIGTERM: $this->output->writeln("Caught SIGTERM"); break;
            default: $this->output->writeln("Caught $signo"); break;
        }
        $this->output->writeln("Deleting stack {$this->currentStackName}");
        $this->stackManager->deleteStack($this->currentStackName);
        exit;
    }
}
