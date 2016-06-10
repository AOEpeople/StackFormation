<?php

namespace StackFormation;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * @deprecated 
 */
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
     * @deprecated
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
     * @deprecated
     */
    public function getBlueprintNameForStack($stackName)
    {
        return $stack = $this->stackFactory->getStack($stackName)->getBlueprintName();
    }

    /**
     * @deprecated
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
     * @deprecated
     */
    protected function resolveWildcard($stackName)
    {
        return $this->stackFactory->resolveWildcard($stackName);
    }

    /**
     * @deprecated
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
     * @deprecated
     */
    public function deleteStack($stackName)
    {
        $stack = new Stack($stackName, $this->getCfnClient());
        return $stack->delete();
    }

    /**
     * @deprecated
     */
    public function getPreprocessedTemplate($blueprintName)
    {
        return $this->blueprintFactory->getBlueprint($blueprintName)->getPreprocessedTemplate();
    }

    /**
     * @deprecated
     */
    public function getTemplate($stackName)
    {
        return $this->stackFactory->getStack($stackName)->getTemplate();
    }

    /**
     * @deprecated
     */
    public function deployStack($blueprintName, $dryRun = false) {
        $this->deployBlueprint($blueprintName, $dryRun);
    }

    /**
     * @deprecated
     */
    public function deployBlueprint($blueprintName, $dryRun = false)
    {
        $this->blueprintFactory->getBlueprint($blueprintName)->deploy($dryRun, $this->stackFactory);
    }

    /**
     * @deprecated
     */
    public function getChangeSet($blueprintName)
    {
        return $this->blueprintFactory->getBlueprint($blueprintName)->getChangeSet(true);
    }

    /**
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
     * @deprecated
     */
    public function getStackStatus($stackName)
    {
        $stack = new Stack($stackName, $this->getCfnClient());
        return $stack->getStatus();
    }

    /**
     * @deprecated
     */
    public function describeStackEvents($stackName)
    {
        $stack = new Stack($stackName, $this->getCfnClient());
        return $stack->getEvents();
    }

    /**
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

    /**
     * @deprecated
     */
    public function getBlueprintParameters($blueprintName, $resolvePlaceholders = true, $flatten = false)
    {
        return $this->blueprintFactory->getBlueprint($blueprintName)->getParameters($resolvePlaceholders, $flatten);
    }

    /**
     * @deprecated
     */
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
