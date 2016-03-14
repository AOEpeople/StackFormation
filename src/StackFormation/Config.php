<?php

namespace StackFormation;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Parser;

class Config
{

    protected $conf;

    protected $stackManager;

    public function __construct()
    {
        $files = $this->findAllConfigurationFiles();
        if (count($files) == 0) {
            throw new \StackFormation\Exception\NoBlueprintsFoundException("Could not find any blueprints.yml configuration files");
        }
        $processor = new Processor();
        $yamlParser = new Parser();

        $config = [];
        foreach ($files as $file) {
            $basePath = dirname(realpath($file));
            $tmp = $yamlParser->parse(file_get_contents($file));
            if (isset($tmp['blueprints']) && is_array($tmp['blueprints'])) {
                foreach ($tmp['blueprints'] as &$blueprintConfig) {
                    $blueprintConfig['basepath'] = $basePath;
                    $blueprintConfig['template'] = (array)$blueprintConfig['template'];
                    foreach ($blueprintConfig['template'] as &$template) {
                        $realPathFile = realpath($basePath . '/' . $template);
                        if ($realPathFile === false) {
                            throw new \Exception('Could not find template file ' . $template);
                        }
                        $template = $realPathFile;
                    }
                    if (isset($blueprintConfig['stackPolicy'])) {
                        $realPathFile = realpath($basePath . '/' . $blueprintConfig['stackPolicy']);
                        if ($realPathFile === false) {
                            throw new \Exception('Could not find stack policy '.$blueprintConfig['stackPolicy'].' referenced in file ' . $template, 1452679777);
                        }
                        $blueprintConfig['stackPolicy'] = $realPathFile;
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

    public static function findAllConfigurationFiles($dirname='blueprints', $filename='blueprints.yml')
    {
        $files = array_merge(
            glob($dirname.'/*/*/'.$filename),
            glob($dirname.'/*/'.$filename),
            glob($dirname.'/'.$filename),
            glob($filename)
        );
        return $files;
    }

    public function blueprintExists($blueprint)
    {
        if (!is_string($blueprint)) {
            throw new \InvalidArgumentException('Invalid blueprint name');
        }
        return isset($this->conf['blueprints'][$blueprint]);
    }

    public function getGlobalVars()
    {
        return isset($this->conf['vars']) ? $this->conf['vars'] : [];
    }

    public function getBlueprintVars($stack)
    {
        if (!is_string($stack)) {
            throw new \InvalidArgumentException('Invalid stack name');
        }
        $blueprintConfig = $this->getBlueprintConfig($stack);
        $localVars = isset($blueprintConfig['vars']) ? $blueprintConfig['vars'] : [];
        return array_merge($this->getGlobalVars(), $localVars);
    }

    public function getBlueprintConfig($stack)
    {
        if (!is_string($stack)) {
            throw new \InvalidArgumentException('Invalid stack name');
        }
        if (!$this->blueprintExists($stack)) {
            throw new \Exception("Stack '$stack' not found.");
        }

        return $this->conf['blueprints'][$stack];
    }

    public function getBlueprintNames()
    {
        $blueprintNames = array_keys($this->conf['blueprints']);
        sort($blueprintNames);
        return $blueprintNames;
    }

    public function getEffectiveStackName($blueprintName)
    {
        return $this->getStackManager()->resolvePlaceholders($blueprintName); // without the stackname parameter obviously...
    }

    protected function getStackManager()
    {
        if (is_null($this->stackManager)) {
            $this->stackManager = new StackManager();
        }
        return $this->stackManager;
    }

    public function getBlueprintTags($blueprintName, $resolvePlaceholders=true)
    {
        $tags = [];
        $stackConfig = $this->getBlueprintConfig($blueprintName);
        if (isset($stackConfig['tags'])) {
            foreach ($stackConfig['tags'] as $key => $value) {
                if ($resolvePlaceholders) {
                    $value = $this->getStackManager()->resolvePlaceholders($value, $blueprintName);
                }
                $tags[] = ['Key' => $key, 'Value' => $value];
            }
        }
        return $tags;
    }

    public function getBlueprintLabels()
    {
        $labels = [];
        foreach ($this->getBlueprintNames() as $blueprintName) {
            try {
                $effectiveStackName = $this->getEffectiveStackName($blueprintName);
            } catch (\Exception $e) {
                $effectiveStackName = '[Missing env var] Error: ' . $e->getMessage();
            }
            $label = $blueprintName;
            if ($effectiveStackName != $blueprintName) {
                $label .= " (Effective: $effectiveStackName)";
            }
            $labels[] = $label;
        }
        return $labels;
    }

    public function convertBlueprintNameIntoRegex($blueprintName)
    {
        return '/^'.preg_replace('/\{[^\}]+?\}/', '(.*)', $blueprintName) .'$/';
    }
}
