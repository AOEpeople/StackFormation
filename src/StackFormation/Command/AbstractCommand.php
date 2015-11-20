<?php

namespace StackFormation\Command;

use StackFormation\StackManager;
use StackFormation\Config;
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

}