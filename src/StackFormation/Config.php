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

    public function getEffectiveStackName($stackName) {
        $stackConfig = $this->getStackConfig($stackName);
        if (!empty($stackConfig['stackname'])) {
            $stackName = $stackConfig['stackname'];
        }
        return preg_replace_callback('/\{env:(.*)\}/', function($matches) {
            if (!getenv($matches[1])) {
                throw new \Exception("Environment variable '{$matches[1]}' not found");
            }
            return getenv($matches[1]);
        }, $stackName);
    }

    public function getStackLabels() {
        $labels = [];
        foreach ($this->getStacknames() as $stackname) {
            try {
                $effectiveStackName = $this->getEffectiveStackName($stackname);
            } catch (\Exception $e) {
                $effectiveStackName = '[Missing env var]';
            }
            $label = $stackname;
            if ($effectiveStackName != $stackname) {
                $label .= " (Effective: $effectiveStackName)";
            }
            $labels[] = $label;
        }
        return $labels;
    }

}
