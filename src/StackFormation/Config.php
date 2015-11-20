<?php

namespace StackFormation;

use Symfony\Component\Yaml\Parser;

class Config
{

    protected $conf;

    public function __construct($file = 'stacks.yml')
    {
        if (!is_file($file)) {
            throw new \Exception("File '$file' not found.");
        }
        $yaml = new Parser();
        $this->conf = $yaml->parse(file_get_contents($file));
    }

    public function stackExists($stack)
    {
        return isset($this->conf['stacks'][$stack]);
    }

    public function getStackConfig($stack)
    {
        if (!$this->stackExists($stack)) {
            throw new \Exception("Stack '$stack' not found.");
        }
        return $this->conf['stacks'][$stack];
    }

    public function getStacknames() {
        return array_keys($this->conf['stacks']);
    }

}
