<?php

namespace StackFormation;

class Blueprint {
    
    /**
     * @var string
     */
    protected $name;
    protected $config;
    protected $resolver;
    protected $cfnClient;

    public function __construct($name, array $config, PlaceholderResolver $resolver, \Aws\CloudFormation\CloudFormationClient $cfnClient)
    {
        $this->name = $name;
        $this->config = $config;
        $this->resolver = $resolver;
        $this->cfnClient = $cfnClient;
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
    
    public function getBeforeScripts($resolvePlaceholders = true)
    {
        $scripts = [];
        if (isset($this->config['before']) && is_array($this->config['before']) && count($this->config['before']) > 0) {
            $scripts = $this->config['before'];
        }
        if ($resolvePlaceholders) {
            foreach ($scripts as &$script) {
                $script = $this->resolver->resolvePlaceholders($script, $this, 'script');
            }
        }
        return $scripts;
    }

    public function getBasePath()
    {
        if (!isset($this->config['basepath']) || !is_dir($this->config['basepath'])) {
            throw new \Exception("Invalid basepath '{$this->config['basepath']}'");
        }
        return $this->config['basepath'];
    }

    public function getCapabilities()
    {
        return isset($this->config['Capabilities']) ? explode(',', $this->config['Capabilities']) : [];
    }

    public function getStackPolicy()
    {
        if (isset($this->config['stackPolicy'])) {
            if (!is_file($this->config['stackPolicy'])) {
                throw new \Exception('Stack policy "' . $this->config['stackPolicy'] . '" not found');
            }
            return file_get_contents($this->config['stackPolicy']);
        }
        return false;
    }

    public function getOnFailure()
    {
        $onFailure = isset($this->config['OnFailure']) ? $this->config['OnFailure'] : 'DO_NOTHING';
        if (!in_array($onFailure, ['ROLLBACK', 'DO_NOTHING', 'DELETE'])) {
            throw new \InvalidArgumentException("Invalid value for onFailure parameter");
        }
        return $onFailure;
    }

    public function prepareArguments()
    {
        $arguments = [
            'StackName' => $this->getStackName(),
            'Parameters' => $this->getParameters(),
            'TemplateBody' => $this->getPreprocessedTemplate(),
            'Capabilities' => $this->getCapabilities(),
            'Tags' => $this->getTags()
        ];
        if ($policy = $this->getStackPolicy()) {
            $arguments['StackPolicyBody'] = $this->getStackPolicy();
        }
        return $arguments;
    }

    public function executeBeforeScripts()
    {
        $scripts = $this->getBeforeScripts();
        $path = $this->getBasePath();

        $cwd = getcwd();
        chdir($path);

        passthru(implode("\n", $scripts), $returnVar);
        if ($returnVar !== 0) {
            throw new \Exception('Error executing commands');
        }
        chdir($cwd);
    }

    public function getChangeSet($verbose=true)
    {
        $arguments = $this->prepareArguments();
        if (isset($arguments['StackPolicyBody'])) {
            unset($arguments['StackPolicyBody']);
        }
        $arguments['ChangeSetName'] = 'stackformation' . time();

        $res = $this->cfnClient->createChangeSet($arguments);
        $changeSetId = $res->get('Id');
        $result = Poller::poll(function() use ($changeSetId, $verbose) {
            $result = $this->cfnClient->describeChangeSet(['ChangeSetName' => $changeSetId]);
            if ($verbose) {
                echo "Status: {$result['Status']}\n";
            }
            if ($result['Status'] == 'FAILED') {
                throw new \Exception($result['StatusReason']);
            }
            return ($result['Status'] != 'CREATE_COMPLETE') ? false : $result;
        });
        return $result;
    }

    public function deploy($dryRun=false, StackFactory $stackFactory)
    {
        $arguments = $this->prepareArguments();

        $stackStatus = $stackFactory->getStack($this->getStackName())->getStatus();
        if (strpos($stackStatus, 'IN_PROGRESS') !== false) {
            throw new \Exception("Stack can't be updated right now. Status: $stackStatus");
        } elseif (!empty($stackStatus) && $stackStatus != 'DELETE_COMPLETE') {
            if (!$dryRun) {
                $this->cfnClient->updateStack($arguments);
            }
        } else {
            $arguments['OnFailure'] = $this->getOnFailure();
            if (!$dryRun) {
                $this->cfnClient->createStack($arguments);
            }
        }
    }

}