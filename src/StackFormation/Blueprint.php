<?php

namespace StackFormation;

use StackFormation\Exception\StackNotFoundException;

class Blueprint {
    
    /**
     * @var string
     */
    protected $name;
    protected $blueprintConfig;
    protected $resolver;
    protected $cfnClient;

    public function __construct($name, array $blueprintConfig, PlaceholderResolver $resolver, \Aws\CloudFormation\CloudFormationClient $cfnClient)
    {
        $this->name = $name;
        $this->blueprintConfig = $blueprintConfig;
        $this->resolver = $resolver;
        $this->cfnClient = $cfnClient;
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
                    $value = $this->resolver->resolvePlaceholders($value, $this, 'tag', $key);
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
        if (isset($this->blueprintConfig['profile'])) {
            $profile = $this->resolver->resolvePlaceholders($this->blueprintConfig['profile'], $this, 'profile');
            if ($profile == 'USE_IAM_INSTANCE_PROFILE') {
                echo "Using IAM instance profile\n";
            } else {
                // TODO: dependency to AwsInspector!
                $profileManager = new \AwsInspector\ProfileManager();
                $profileManager->loadProfile($profile);
                echo "Loading Profile: $profile\n";
            }
        }
    }

    public function getPreprocessedTemplate()
    {
        $this->enforceProfile();

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

    public function getParameters($resolvePlaceholders = true, $flatten = false)
    {
        $parameters = [];

        $this->enforceProfile();

        if (isset($this->blueprintConfig['parameters'])) {
            foreach ($this->blueprintConfig['parameters'] as $parameterKey => $parameterValue) {
                $tmp = ['ParameterKey' => $parameterKey];
                if (is_null($parameterValue)) {
                    $tmp['UsePreviousValue'] = true;
                } else {
                    if ($resolvePlaceholders) {
                        $parameterValue = $this->resolver->resolvePlaceholders($parameterValue, $this, 'parameter', $parameterKey);
                    }
                    $tmp['ParameterValue'] = $parameterValue;
                }
                if (strpos($tmp['ParameterKey'], '*') !== false) {
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
        if (isset($this->blueprintConfig['before']) && is_array($this->blueprintConfig['before']) && count($this->blueprintConfig['before']) > 0) {
            $scripts = $this->blueprintConfig['before'];
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
        if (!isset($this->blueprintConfig['basepath']) || !is_dir($this->blueprintConfig['basepath'])) {
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
                throw new \Exception('Stack policy "' . $this->blueprintConfig['stackPolicy'] . '" not found');
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

    public function prepareArguments()
    {
        $arguments = [
            'StackName' => $this->getStackName(),
            'Parameters' => $this->getParameters(),
            'TemplateBody' => $this->getPreprocessedTemplate(),
            'Tags' => $this->getTags()
        ];
        if ($capabilities = $this->getCapabilities()) {
            $arguments['Capabilities'] = $capabilities;
        }
        if ($policy = $this->getStackPolicy()) {
            $arguments['StackPolicyBody'] = $this->getStackPolicy();
        }

        // this is how we reference a stack back to its blueprint
        $blueprintReference = array_merge(
            ['Name' => $this->name],
            $this->resolver->getDependencyTracker()->getUsedEnvironmentVariables()
        );

        $arguments['Tags'][] = [
            'Key' => 'stackformation:blueprint',
            'Value' => base64_encode(http_build_query($blueprintReference))
        ];
        return $arguments;
    }

    public function executeBeforeScripts()
    {
        $scripts = $this->getBeforeScripts();
        if (count($scripts) == 0) {
            return;
        }

        $cwd = getcwd();
        chdir($this->getBasePath());

        passthru(implode("\n", $scripts), $returnVar);
        if ($returnVar !== 0) {
            throw new \Exception('Error executing commands');
        }
        chdir($cwd);
    }

    public function validateTemplate()
    {
        $this->cfnClient->validateTemplate(['TemplateBody' => $this->getPreprocessedTemplate()]);
        // will throw an exception if there's a problem
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

        try {
            $stackStatus = $stackFactory->getStack($this->getStackName())->getStatus();
        } catch (StackNotFoundException $e) {
            $stackStatus = false;
        }

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

    public function gatherDependencies()
    {
        $this->getParameters();
        $this->getPreprocessedTemplate();
    }

}