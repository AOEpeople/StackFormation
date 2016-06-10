<?php

namespace StackFormation;

class Blueprint {
    
    /**
     * @var string
     */
    protected $name;
    protected $config;
    protected $resolver;

    public function __construct($name, array $config, PlaceholderResolver $resolver)
    {
        $this->name = $name;
        $this->config = $config;
        $this->resolver = $resolver;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getTags($resolvePlaceholders=true)
    {
        $tags = [
            ['Key' => 'stackformation:blueprint', 'Value' => base64_encode($this->name)] // this is how we reference a stack back to its blueprint
        ];

        if (isset($this->config['tags'])) {
            foreach ($this->config['tags'] as $key => $value) {
                if ($resolvePlaceholders) {
                    $value = $this->resolver->resolvePlaceholders($value, $this, "tag:$key");
                }
                $tags[] = ['Key' => $key, 'Value' => $value];
            }
        }
        return $tags;
    }

    public function getStackName()
    {
        return $this->resolver->resolvePlaceholders($this->name, $this, 'stackname');
    }

    public function enforceProfile()
    {
        // TODO: loading profiles shouldn't be done within a blueprint!
        if (isset($this->config['profile'])) {
            $profile = $this->resolver->resolvePlaceholders($this->config['profile'], $this, 'profile');
            if ($profile == 'USE_IAM_INSTANCE_PROFILE') {
                echo "Using IAM instance profile\n";
            } else {
                $profileManager = new \AwsInspector\ProfileManager();
                $profileManager->loadProfile($profile);
                echo "Loading Profile: $profile\n";
            }
        }
    }

    public function getPreprocessedTemplate()
    {
        $this->enforceProfile();

        if (empty($this->config['template']) || !is_array($this->config['template'])) {
            throw new \Exception('No template(s) found');
        }

        $preProcessor = new Preprocessor();

        $templateContents = [];
        foreach ($this->config['template'] as $key => $template) {
            $templateContents[$key] = $preProcessor->process($template);
        }

        $templateMerger = new TemplateMerger();
        $description = !empty($this->config['description']) ? $this->config['description'] : null;
        return $templateMerger->merge($templateContents, $description);
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function getParameters($resolvePlaceholders = true, $flatten = false)
    {
        $parameters = [];

        $this->enforceProfile();

        if (isset($this->config['parameters'])) {
            foreach ($this->config['parameters'] as $parameterKey => $parameterValue) {
                $tmp = ['ParameterKey' => $parameterKey];
                if (is_null($parameterValue)) {
                    $tmp['UsePreviousValue'] = true;
                } else {
                    if ($resolvePlaceholders) {
                        $parameterValue = $this->resolver->resolvePlaceholders($parameterValue, $this, "parameter:$parameterKey");
                    }
                    $tmp['ParameterValue'] = $parameterValue;
                }
                if (strpos($tmp['ParameterKey'], '*') !== false) {
                    $count = 0;
                    foreach (array_keys($this->config['template']) as $key) {
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
        }

        if ($flatten) {
            $tmp = [];
            foreach ($parameters as $parameter) {
                $tmp[$parameter['ParameterKey']] = $parameter['ParameterValue'];
            }
            return $tmp;
        }

        return $parameters;
    }


}