<?php

namespace StackFormation;

use Aws\CloudFormation\Exception\CloudFormationException;
use Symfony\Component\Console\Output\OutputInterface;

class StackManager
{

    protected $dependencyTracker;

    protected $stackFactory;
    protected $blueprintFactory;

    /**
     * @deprecated
     */
    protected $config;

    public function __construct()
    {
        $this->dependencyTracker = new DependencyTracker();
        $this->stackFactory = new StackFactory();
        $this->blueprintFactory = new BlueprintFactory();
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

    /**
     * @param $blueprintName
     * @return string
     * @throws \Exception
     * @deprecated
     */
    public function getPreprocessedTemplate($blueprintName)
    {
        return $this->blueprintFactory->getBlueprint($blueprintName)->getPreprocessedTemplate();
    }

    /**
     * @param $stackName
     * @return mixed|null
     * @deprecated
     */
    public function getTemplate($stackName)
    {
        return $this->stackFactory->getStack($stackName)->getTemplate();
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
     * @deprecated
     */
    public function resolvePlaceholders($string, $blueprintName=null, $type=null)
    {
        if ($blueprintName) {
            $blueprint = $this->blueprintFactory->getBlueprint($blueprintName);
        } else {
            $blueprint = null;
        }
        $resolver = new PlaceholderResolver($this->dependencyTracker, $this->stackFactory);
        return $resolver->resolvePlaceholders($string, $blueprint, $type);
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
