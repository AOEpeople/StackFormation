<?php

namespace StackFormation;

use Aws\CloudFormation\Exception\CloudFormationException;
use Symfony\Component\Console\Output\OutputInterface;

class StackManager
{

    protected $dependencyTracker;

    protected $stackFactory;

    protected $config;

    public function __construct()
    {
        $this->dependencyTracker = new DependencyTracker();
        $this->stackFactory = new StackFactory();
    }

    /**
     * Get parameter values for stack
     *
     * @deprecated
     *
     * @param      $stackName
     * @param null $key
     *
     * @return mixed
     * @throws \Exception
     */
    public function getParameters($stackName, $key = null)
    {
        $stack = $this->stackFactory->getStack($stackName);
        if (!is_null($key)) {
            return $stack->getParameter($key);
        } else {
            return $stack->getParameters();
        }
    }

    /**
     * @return \Aws\CloudFormation\CloudFormationClient
     */
    protected function getCfnClient()
    {
        return SdkFactory::getCfnClient();
    }

    /**
     * Get output values for stack
     *
     * @deprecated
     * @param      $stackName
     * @param null $key
     *
     * @return mixed
     * @throws \Exception
     */
    public function getOutputs($stackName, $key = null)
    {
        $stack = $this->stackFactory->getStack($stackName);
        if (!is_null($key)) {
            return $stack->getOutput($key);
        } else {
            return $stack->getOutputs();
        }
    }

    /**
     * @deprecated
     */
    public function getTags($stackName, $key = null)
    {
        $stack = $this->stackFactory->getStack($stackName);
        if (!is_null($key)) {
            return $stack->getTag($key);
        } else {
            return $stack->getTags();
        }
    }

    /**
     * @param $stackName
     * @return null|string
     * @deprecated
     */
    public function getBlueprintNameForStack($stackName)
    {
        return $stack = $this->stackFactory->getStack($stackName)->getBlueprintName();
    }

    /**
     * Get output values for stack
     *
     * @param      $stackName
     * @param null $LogicalResourceId
     * @deprecated
     *
     * @return mixed
     * @throws \Exception
     */
    public function getResources($stackName, $LogicalResourceId = null)
    {
        $stack = $this->stackFactory->getStack($stackName);
        if (!is_null($LogicalResourceId)) {
            return $stack->getResource($LogicalResourceId);
        } else {
            return $stack->getResources();
        }
    }

    /**
     * Resolve wildcard
     *
     * @param $stackName
     * @return mixed
     * @throws \Exception
     * @deprecated
     */
    protected function resolveWildcard($stackName)
    {
        return $this->stackFactory->resolveWildcard($stackName);
    }

    /**
     * @deprecated
     * @return array
     */
    public function getStacksFromApi($fresh = false, $nameFilter=null, $statusFilter=null)
    {
        $stackFactory = new StackFactory($this->getCfnClient());
        return $stackFactory->getStacksFromApi($fresh, $nameFilter, $statusFilter);
    }

    /**
     * @deprecated
     */
    public function cancelUpdate($stackName)
    {
        $stack = new Stack($stackName, $this->getCfnClient());
        return $stack->cancelUpdate();
    }

    /**
     * @param $stackName
     * @return bool
     * @deprecated
     */
    public function deleteStack($stackName)
    {
        $stack = new Stack($stackName, $this->getCfnClient());
        return $stack->delete();
    }

    public function validateTemplate($blueprintName)
    {
        $res = $this->getCfnClient()->validateTemplate([
            'TemplateBody' => $this->getPreprocessedTemplate($blueprintName)
        ]);

        // will throw an exception if there's a problem
    }

    public function getPreprocessedTemplate($blueprintName)
    {
        $stackConfig = $this->getConfig()->getBlueprintConfig($blueprintName);

        if (isset($stackConfig['profile'])) {
            $profile = $this->resolvePlaceholders($stackConfig['profile'], $blueprintName, 'profile');
            if ($profile == 'USE_IAM_INSTANCE_PROFILE') {
                echo "Using IAM instance profile\n";
            } else {
                $profileManager = new \AwsInspector\ProfileManager();
                $profileManager->loadProfile($profile);
                echo "Loading Profile: $profile\n";
            }
        }

        if (empty($stackConfig['template']) || !is_array($stackConfig['template'])) {
            throw new \Exception('No template(s) found');
        }

        $preProcessor = new Preprocessor();

        $templateContents = [];
        foreach ($stackConfig['template'] as $key => $template) {
            $templateContents[$key] = $preProcessor->process($template);
        }

        $templateMerger = new TemplateMerger();
        $description = !empty($stackConfig['description']) ? $stackConfig['description'] : null;
        return $templateMerger->merge($templateContents, $description);
    }

    public function getTemplate($stackName)
    {
        $stackName = $this->resolveWildcard($stackName);

        $res = $this->getCfnClient()->getTemplate(['StackName' => $stackName]);

        return $res->get("TemplateBody");
    }

    protected function prepareArguments($blueprintName)
    {
        $stackConfig = $this->getConfig()->getBlueprintConfig($blueprintName);

        if (isset($stackConfig['account'])) {
            $configuredAccountId = $this->resolvePlaceholders($stackConfig['account'], $blueprintName, 'account');
            if ($configuredAccountId != $this->getConfig()->getCurrentUsersAccountId()) {
                throw new \Exception(sprintf("Current user's AWS account id '%s' does not match the one configured in the blueprint: '%s'",
                    $this->getConfig()->getCurrentUsersAccountId(),
                    $configuredAccountId
                ));
            }
        }

        if (isset($stackConfig['profile'])) {
            $profile = $this->resolvePlaceholders($stackConfig['profile'], $blueprintName, 'profile');
            if ($profile == 'USE_IAM_INSTANCE_PROFILE') {
                echo "Using IAM instance profile\n";
            } else {
                $profileManager = new \AwsInspector\ProfileManager();
                $profileManager->loadProfile($profile);
                echo "Loading Profile: $profile\n";
            }
        }

        $arguments = [
            'StackName' => $this->getConfig()->getEffectiveStackName($blueprintName),
            'Parameters' => $this->getBlueprintParameters($blueprintName),
            'TemplateBody' => $this->getPreprocessedTemplate($blueprintName),
        ];

        if (isset($stackConfig['before']) && is_array($stackConfig['before']) && count($stackConfig['before']) > 0) {
            $this->executeScripts($stackConfig['before'], $stackConfig['basepath'], $blueprintName);
        }

        if (isset($stackConfig['Capabilities'])) {
            $arguments['Capabilities'] = explode(',', $stackConfig['Capabilities']);
        }

        if (isset($stackConfig['stackPolicy'])) {
            if (!is_file($stackConfig['stackPolicy'])) {
                throw new \Exception('Stack policy "' . $stackConfig['stackPolicy'] . '" not found', 1452687982);
            }
            $arguments['StackPolicyBody'] = file_get_contents($stackConfig['stackPolicy']);
        }

        $arguments['Tags'] = $this->getConfig()->getBlueprintTags($blueprintName);

        return $arguments;
    }

    /**
     * @param $blueprintName
     * @param bool $dryRun
     * @throws \Exception
     * @deprecated 
     */
    public function deployStack($blueprintName, $dryRun = false) {
        return $this->deployBlueprint($blueprintName, $dryRun);
    }

    /**
     * Deploy Blueprint
     *
     * @param string $blueprintName
     * @param bool $dryRun
     * @throws \Exception
     */
    public function deployBlueprint($blueprintName, $dryRun = false)
    {
        $arguments = $this->prepareArguments($blueprintName);

        $stackStatus = $this->getStackStatus($arguments['StackName']);

        if (strpos($stackStatus, 'IN_PROGRESS') !== false) {

            throw new \Exception("Stack can't be updated right now. Status: $stackStatus");

        } elseif (!empty($stackStatus) && $stackStatus != 'DELETE_COMPLETE') {

            if (!$dryRun) {
                $this->getCfnClient()->updateStack($arguments);
            }

        } else {

            $onFailure = isset($stackConfig['OnFailure']) ? $stackConfig['OnFailure'] : 'DO_NOTHING';
            if (!in_array($onFailure, ['ROLLBACK', 'DO_NOTHING', 'DELETE'])) {
                throw new \InvalidArgumentException("Invalid value for onFailure parameter");
            }
            $arguments['OnFailure'] = $onFailure;

            if (!$dryRun) {
                $this->getCfnClient()->createStack($arguments);
            }

        }
    }

    public function getChangeSet($blueprintName)
    {
        $arguments = $this->prepareArguments($blueprintName);
        if (isset($arguments['StackPolicyBody'])) {
            unset($arguments['StackPolicyBody']);
        }

        $arguments['ChangeSetName'] = 'stackformation' . time();

        $client = $this->getCfnClient();

        $res = $client->createChangeSet($arguments);
        $changeSetId = $res->get('Id');

        $result = Poller::poll(function() use ($client, $changeSetId) {
            $result = $client->describeChangeSet([ 'ChangeSetName' => $changeSetId ]);
            echo "Status: {$result['Status']}\n";
            if ($result['Status'] == 'FAILED') {
                throw new \Exception($result['StatusReason']);
            }
            return ($result['Status'] != 'CREATE_COMPLETE') ? false : $result;
        });

        return $result;
    }

    protected function executeScripts(array $scripts, $path, $stackName = null)
    {
        $cwd = getcwd();
        chdir($path);

        foreach ($scripts as &$script) {
            $script = $this->resolvePlaceholders($script, $stackName, 'scripts');
        }
        passthru(implode("\n", $scripts), $returnVar);
        if ($returnVar !== 0) {
            throw new \Exception('Error executing commands');
        }
        chdir($cwd);
    }

    /**
     * @param $stackName
     * @param OutputInterface $output
     * @param int $pollInterval
     * @param bool $deleteOnSignal
     * @return int
     * @deprecated
     */
    public function observeStackActivity($stackName, OutputInterface $output, $pollInterval = 10, $deleteOnSignal=false)
    {
        $stack = $this->stackFactory->getStack($stackName);
        $observer = new Observer($stack, $output);
        if ($deleteOnSignal) {
            $observer->deleteOnSignal();
        }
        $returnValue = $observer->observeStackActivity($pollInterval);
        return $returnValue;
    }

    /**
     * @param $stackName
     * @return null
     * @throws \Exception
     * @deprecated
     */
    public function getStackStatus($stackName)
    {
        $stack = new Stack($stackName, $this->getCfnClient());
        return $stack->getStatus();
    }

    /**
     * @param $stackName
     * @return array
     * @deprecated
     */
    public function describeStackEvents($stackName)
    {
        $stack = new Stack($stackName, $this->getCfnClient());
        return $stack->getEvents();
    }

    /**
     * Resolve placeholders
     *
     * @param $string
     * @param null $blueprintName (optional) will be used to load blueprint specific vars and will be appended to Exception message for debugging purposes
     * @param string $type (optional) will be appended to Exception message for debugging purposes
     * @return mixed
     * @throws \Exception
     */
    public function resolvePlaceholders($string, $blueprintName=null, $type=null)
    {
        $originalString = $string;

        // {env:...}
        $string = preg_replace_callback(
            '/\{env:([^:\}\{]+?)\}/',
            function ($matches) use ($blueprintName, $type) {
                $this->dependencyTracker->trackEnvUsage($matches[1]);
                if (!getenv($matches[1])) {
                    throw new \Exception("Environment variable '{$matches[1]}' not found (Blueprint: $blueprintName, Type: $type)");
                }
                return getenv($matches[1]);
            },
            $string
        );

        // {env:...:...} (with default value if env var is not set)
        $string = preg_replace_callback(
            '/\{env:([^:\}\{]+?):([^:\}\{]+?)\}/',
            function ($matches) {
                $this->dependencyTracker->trackEnvUsage($matches[1], true);
                if (!getenv($matches[1])) {
                    return $matches[2];
                }
                return getenv($matches[1]);
            },
            $string
        );

        // {var:...}
        $vars = $blueprintName ? $this->getConfig()->getBlueprintVars($blueprintName) : $this->getConfig()->getGlobalVars();
        $string = preg_replace_callback(
            '/\{var:([^:\}\{]+?)\}/',
            function ($matches) use ($vars, $blueprintName, $type) {
                if (!isset($vars[$matches[1]])) {
                    throw new \Exception("Variable '{$matches[1]}' not found (Blueprint: $blueprintName, Type: $type)");
                }
                return $vars[$matches[1]];
            },
            $string
        );

        // {tstamp}
        static $time;
        if (!isset($time)) {
            $time = time();
        }
        $string = str_replace('{tstamp}', $time, $string);

        // {output:...:...}
        $string = preg_replace_callback(
            '/\{output:([^:\}\{]+?):([^:\}\{]+?)\}/',
            function ($matches) use ($blueprintName, $type) {
                try {
                    $this->dependencyTracker->trackStackDependency('output', $matches[1], $matches[2]);
                    return $this->getOutputs($matches[1], $matches[2]);
                } catch (CloudFormationException $e) {
                    $extractedMessage = Helper::extractMessage($e);
                    throw new \Exception("Error resolving '{$matches[0]}' (Blueprint: $blueprintName, Type: $type) (CloudFormation error: $extractedMessage)");
                }
            },
            $string
        );

        // {resource:...:...}
        $string = preg_replace_callback(
            '/\{resource:([^:\}\{]+?):([^:\}\{]+?)\}/',
            function ($matches) use ($blueprintName, $type) {
                try {
                    $this->dependencyTracker->trackStackDependency('resource', $matches[1], $matches[2]);
                    return $this->getResources($matches[1], $matches[2]);
                } catch (CloudFormationException $e) {
                    $extractedMessage = Helper::extractMessage($e);
                    throw new \Exception("Error resolving '{$matches[0]}' (Blueprint: $blueprintName, Type: $type) (CloudFormation error: $extractedMessage)");
                }
            },
            $string
        );

        // {parameter:...:...}
        $string = preg_replace_callback(
            '/\{parameter:([^:\}\{]+?):([^:\}\{]+?)\}/',
            function ($matches) use ($blueprintName, $type) {
                try {
                    $this->dependencyTracker->trackStackDependency('parameter', $matches[1], $matches[2]);
                    return $this->getParameters($matches[1], $matches[2]);
                } catch (CloudFormationException $e) {
                    $extractedMessage = Helper::extractMessage($e);
                    throw new \Exception("Error resolving '{$matches[0]}' (Blueprint: $blueprintName, Type: $type) (CloudFormation error: $extractedMessage)");
                }
            },
            $string
        );

        // {clean:...}
        $string = preg_replace_callback(
            '/\{clean:([^:\}\{]+?)\}/',
            function ($matches) {
                return preg_replace('/[^-a-zA-Z0-9]/', '', $matches[1]);
            },
            $string
        );

        // recursively continue until everything is replaced
        if ($string != $originalString) {
            $string = $this->resolvePlaceholders($string, $blueprintName, $type);
        }

        return $string;
    }

    /**
     * @deprecated
     */
    public function getParametersFromConfig($blueprintName, $resolvePlaceholders = true, $flatten = false)
    {
        return $this->getBlueprintParameters($blueprintName, $resolvePlaceholders, $flatten);
    }

    public function getBlueprintParameters($blueprintName, $resolvePlaceholders = true, $flatten = false)
    {
        $stackConfig = $this->getConfig()->getBlueprintConfig($blueprintName);

        $parameters = [];

        if (isset($stackConfig['profile'])) {
            $profile = $this->resolvePlaceholders($stackConfig['profile'], $blueprintName, 'profile');
            if ($profile == 'USE_IAM_INSTANCE_PROFILE') {
                echo "Using IAM instance profile\n";
            } else {
                $profileManager = new \AwsInspector\ProfileManager();
                $profileManager->loadProfile($profile);
                echo "Loading Profile: $profile\n";
            }
        }


        if (isset($stackConfig['parameters'])) {
            foreach ($stackConfig['parameters'] as $parameterKey => $parameterValue) {
                $tmp = ['ParameterKey' => $parameterKey];
                if (is_null($parameterValue)) {
                    $tmp['UsePreviousValue'] = true;
                } else {
                    $tmp['ParameterValue'] = $resolvePlaceholders ? $this->resolvePlaceholders($parameterValue, $blueprintName, "parameter:$parameterKey") : $parameterValue;
                }
                if (strpos($tmp['ParameterKey'], '*') !== false) {
                    $count = 0;
                    foreach (array_keys($stackConfig['template']) as $key) {
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

    public function getConfig()
    {
        if (is_null($this->config)) {
            $this->config = new Config();
        }
        return $this->config;
    }

    public function getDependencyTracker()
    {
        return $this->dependencyTracker;
    }
}
