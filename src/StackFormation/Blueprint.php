<?php

namespace StackFormation;

class Blueprint {
    
    /**
     * @var string
     */
    protected $name;
    protected $blueprintConfig;
    protected $valueResolver;

    public function __construct($name, array $blueprintConfig, ValueResolver $valueResolver)
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException('Name must be a string');
        }
        $this->name = $name;
        $this->blueprintConfig = $blueprintConfig;
        $this->valueResolver = $valueResolver;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getTags($resolvePlaceholders=true)
    {
        $tags = [];
        if (isset($this->blueprintConfig['tags'])) {
            foreach ($this->blueprintConfig['tags'] as $key => $value) {
                if ($resolvePlaceholders) {
                    $value = $this->valueResolver->resolvePlaceholders($value, $this, 'tag', $key);
                }
                $tags[] = ['Key' => $key, 'Value' => $value];
            }
        }
        return $tags;
    }

    public function getStackName()
    {
        return $this->valueResolver->resolvePlaceholders($this->name, $this, 'stackname');
    }

    public function getProfile($resolvePlaceholders=true)
    {
        if (isset($this->blueprintConfig['profile'])) {
            $value = $this->blueprintConfig['profile'];
            if ($resolvePlaceholders) {
                $value = $this->valueResolver->resolvePlaceholders($this->blueprintConfig['profile'], $this, 'profile');
            }
            return $value;
        }
        return null;
    }

    public function getPreprocessedTemplate()
    {
        if (empty($this->blueprintConfig['template']) || !is_array($this->blueprintConfig['template'])) {
            throw new \Exception('No template(s) found');
        }

        $preProcessor = new Preprocessor();

        $templateContents = [];
        foreach ($this->blueprintConfig['template'] as $key => $template) {
            $templateContents[$key] = $preProcessor->processFile($template);
        }

        $templateMerger = new TemplateMerger();
        $description = !empty($this->blueprintConfig['description']) ? $this->blueprintConfig['description'] : null;
        return $templateMerger->merge($templateContents, $description);
    }

    public function getBlueprintConfig()
    {
        return $this->blueprintConfig;
    }

    public function getParameters($resolvePlaceholders=true)
    {
        $parameters = [];

        if (!isset($this->blueprintConfig['parameters'])) {
            return [];
        }

        foreach ($this->blueprintConfig['parameters'] as $parameterKey => $parameterValue) {

            if (!preg_match('/^[\*A-Za-z0-9]{1,255}$/', $parameterKey)) {
                throw new \Exception("Invalid parameter key '$parameterKey'.");
            }

            if (is_null($parameterValue)) {
                throw new \Exception("Parameter $parameterKey is null.");
            }

            if ($resolvePlaceholders) {
                $parameterValue = $this->valueResolver->resolvePlaceholders($parameterValue, $this, 'parameter', $parameterKey);
            }
            if (!is_scalar($parameterValue)) {
                throw new \Exception('Invalid type for value');
            }

            $tmp = [
                'ParameterKey' => $parameterKey,
                'ParameterValue' => $parameterValue
            ];

            if (strpos($tmp['ParameterKey'], '*') !== false) {
                // resolve the '*' when using multiple templates with prefixes
                if (!is_array($this->blueprintConfig['template'])) {
                    throw new \Exception("Found placeholder ('*') in parameter key but only a single template is used.");
                }

                $count = 0;
                foreach (array_keys($this->blueprintConfig['template']) as $key) {
                    if (!is_int($key)) {
                        $count++;
                        $newParameter = $tmp;
                        $newParameter['ParameterKey'] = str_replace('*', $key, $tmp['ParameterKey']);
                        $parameters[] = $newParameter;
                    }
                }
                if ($count == 0) {
                    throw new \Exception('Found placeholder \'*\' in parameter key but the templates don\'t use prefixes');
                }
            } else {
                $parameters[] = $tmp;
            }
        }

        return $parameters;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getBeforeScripts()
    {
        $scripts = [];
        if (isset($this->blueprintConfig['before']) && is_array($this->blueprintConfig['before']) && count($this->blueprintConfig['before']) > 0) {
            $scripts = $this->blueprintConfig['before'];
        }
        foreach ($scripts as &$script) {
            $script = $this->valueResolver->resolvePlaceholders($script, $this, 'script');
            $script = str_replace('###CWD###', CWD, $script);
        }
        return $scripts;
    }

    public function getBasePath()
    {
        if (empty($this->blueprintConfig['basepath'])) {
            throw new \Exception("No basepath set");
        }
        if (!is_dir($this->blueprintConfig['basepath'])) {
            throw new \Exception("Invalid basepath '{$this->blueprintConfig['basepath']}'");
        }
        return $this->blueprintConfig['basepath'];
    }

    public function getCapabilities()
    {
        return isset($this->blueprintConfig['Capabilities']) ? explode(',', $this->blueprintConfig['Capabilities']) : [];
    }

    public function getStackPolicy()
    {
        if (isset($this->blueprintConfig['stackPolicy'])) {
            if (!is_file($this->blueprintConfig['stackPolicy'])) {
                throw new \Symfony\Component\Filesystem\Exception\FileNotFoundException('Stack policy "' . $this->blueprintConfig['stackPolicy'] . '" not found');
            }
            return file_get_contents($this->blueprintConfig['stackPolicy']);
        }
        return false;
    }

    public function getOnFailure()
    {
        $onFailure = isset($this->blueprintConfig['OnFailure']) ? $this->blueprintConfig['OnFailure'] : 'DO_NOTHING';
        if (!in_array($onFailure, ['ROLLBACK', 'DO_NOTHING', 'DELETE'])) {
            throw new \InvalidArgumentException("Invalid value for onFailure parameter");
        }
        return $onFailure;
    }

    public function getVars()
    {
        return isset($this->blueprintConfig['vars']) ? $this->blueprintConfig['vars'] : [];
    }

    public function getBlueprintReference()
    {
        // this is how we reference a stack back to its blueprint
        $blueprintReference = array_merge(
            ['Name' => $this->name],
            $this->valueResolver->getDependencyTracker()->getUsedEnvironmentVariables()
        );

        $encodedValues = http_build_query($blueprintReference);

        $reference = base64_encode($encodedValues);
        if (strlen($reference) > 255) {
            $encodedValues = 'gz:'.gzencode($encodedValues, 9);
            $reference = base64_encode($encodedValues);
        }
        if (strlen($reference) > 255) {
            throw new \Exception('Blueprint reference too long (even after compression): ' . strlen($reference) . ' chars');
        }
        return $reference;
    }

    public function gatherDependencies()
    {
        $this->getParameters();
        $this->getPreprocessedTemplate();
    }

}