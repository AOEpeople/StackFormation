<?php

namespace StackFormation\Command;

use StackFormation\StackManager;
use StackFormation\Config;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

abstract class AbstractCommand extends Command
{

    /**
     * @var StackManager
     */
    protected $stackManager;

    /**
     * @var Config
     */
    protected $config;

    public function __construct($name = null)
    {
        $this->stackManager = new StackManager();
        $this->config = new Config();

        parent::__construct($name);
    }


    protected function interact_askForConfigStack(InputInterface $input, OutputInterface $output)
    {
        $stack = $input->getArgument('stack');
        if (empty($stack)) {
            $dialog = $this->getHelper('dialog');
            /* @var $dialog \Symfony\Component\Console\Helper\DialogHelper */
            $stacksFromConfig = $this->config->getStackLabels();

            $stack = $dialog->select(
                $output,
                'Please select a stack',
                $stacksFromConfig
            );
            list($stackName) = explode(' ', $stacksFromConfig[$stack]);
            $input->setArgument('stack', $stackName);
        }
        return $stack;
    }

    public function interact_askForLiveStack(InputInterface $input, OutputInterface $output) {
        $stack = $input->getArgument('stack');
        if (empty($stack)) {
            $dialog = $this->getHelper('dialog');
            /* @var $dialog \Symfony\Component\Console\Helper\DialogHelper */
            $stacksFromApi = array_keys($this->stackManager->getStacksFromApi());

            $stack = $dialog->select(
                $output,
                'Please select a stack',
                $stacksFromApi
            );
            $input->setArgument('stack', $stacksFromApi[$stack]);
        }
        return $stack;
    }

}