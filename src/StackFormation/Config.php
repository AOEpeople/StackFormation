<?php

namespace StackFormation;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Yaml\Parser;


class Config
{

    protected $conf;

    public function __construct()
    {
        $files = $this->findAllConfigurationFiles();
        if (count($files) == 0) {
            throw new \Exception("Could not find any stacks.yml configuration files");
        }
        $processor = new Processor();
        $yamlParser = new Parser();

        $config = [];
        foreach ($files as $file) {
            $basePath = dirname(realpath($file));
            $tmp = $yamlParser->parse(file_get_contents($file));
            if (isset($tmp['stacks']) && is_array($tmp['stacks'])) {
                foreach ($tmp['stacks'] as &$stackConfig) {
                    $stackConfig['template'] = (array)$stackConfig['template'];
                    foreach($stackConfig['template'] as &$template) {
                        $realPathFile = realpath($basePath . '/' . $template);
                        if ($realPathFile === false) {
                            throw new \Exception('Could not find template file ' . $template);
                        }
                        $template = $realPathFile;
                    }
                }
            }
            $config[] = $tmp;
        }

        $this->conf = $processor->processConfiguration(
            new ConfigTreeBuilder(),
            $config
        );
    }

    protected function findAllConfigurationFiles()
    {
        $files = array_merge(
            glob('stacks/*/*/stacks.yml'),
            glob('stacks/*/stacks.yml'),
            glob('stacks/stacks.yml'),
            glob('stacks.yml')
        );
        return $files;
    }

    public function stackExists($stack)
    {
        if (!is_string($stack)) {
            throw new \InvalidArgumentException('Invalid stack name');
        }
        return isset($this->conf['stacks'][$stack]);
    }

    public function getGlobalVars()
    {
        return isset($this->conf['vars']) ? $this->conf['vars'] : [];
    }

    public function getStackVars($stack)
    {
        if (!is_string($stack)) {
            throw new \InvalidArgumentException('Invalid stack name');
        }
        $stackConfig = $this->getStackConfig($stack);
        $localVars = isset($stackConfig['vars']) ? $stackConfig['vars'] : [];
        return array_merge($this->getGlobalVars(), $localVars);
    }

    public function getStackConfig($stack)
    {
        if (!is_string($stack)) {
            throw new \InvalidArgumentException('Invalid stack name');
        }
        if (!$this->stackExists($stack)) {
            throw new \Exception("Stack '$stack' not found.");
        }

        return $this->conf['stacks'][$stack];
    }

    public function getStacknames()
    {
        return array_keys($this->conf['stacks']);
    }

    public function getEffectiveStackName($stackName)
    {
        $stackManager = new StackManager();
        return $stackManager->resolvePlaceholders($stackName); // without the stackname parameter obviously...
    }

    public function getStackTags($stackName, $resolvePlaceholders = true)
    {
        $tags = [];
        $stackConfig = $this->getStackConfig($stackName);
        $stackManager = new StackManager();
        if (isset($stackConfig['tags'])) {
            foreach ($stackConfig['tags'] as $key => $value) {
                $tags[] = [
                    'Key' => $key,
                    'Value' => $resolvePlaceholders ? $stackManager->resolvePlaceholders($value, $stackName) : $value
                ];
            }
        }

        return $tags;
    }

    public function getStackLabels()
    {
        $labels = [];
        foreach ($this->getStacknames() as $stackname) {
            try {
                $effectiveStackName = $this->getEffectiveStackName($stackname);
            } catch (\Exception $e) {
                $effectiveStackName = '[Missing env var] Error: ' . $e->getMessage();
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
