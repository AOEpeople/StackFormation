<?php

namespace StackFormation;

use StackFormation\Exception\StackNotFoundException;
use StackFormation\Helper\Finder;

class StackFactory {

    protected $cfnClient;
    protected $stacksCache;

    public function __construct(\Aws\CloudFormation\CloudFormationClient $cfnClient)
    {
        $this->cfnClient = $cfnClient;
    }

    /**
     * @param $stackName
     * @return Stack $stack
     * @throws \Exception
     */
    public function getStack($stackName, $fresh=false)
    {
        $stackName = $this->resolveWildcard($stackName);
        $stacksFromApi = $this->getStacksFromApi($fresh);
        if (!isset($stacksFromApi[$stackName])) {
            throw new StackNotFoundException($stackName);
        }
        return $stacksFromApi[$stackName];
    }

    public function getStackResource($stackName, $key)
    {
        return $this->getStack($stackName)->getResource($key);
    }

    public function getStackOutput($stackName, $key)
    {
        return $this->getStack($stackName)->getOutput($key);
    }

    public function getStackParameter($stackName, $key)
    {
        return $this->getStack($stackName)->getParameter($key);
    }

    public function getStackStatus($stackName)
    {
        return $this->getStack($stackName)->getStatus();
    }

    /**
     * Resolve wildcard
     *
     * @param $stackName
     * @return mixed
     * @throws \Exception
     */
    public function resolveWildcard($stackName)
    {
        if (strpos($stackName, '*') === false) {
            return $stackName;
        }

        $helper = new \StackFormation\Helper();
        $stacks = Finder::find($stackName, array_keys($this->getStacksFromApi()));

        if (count($stacks) == 0) {
            throw new \Exception("No matching stack found for '$stackName'");
        } elseif (count($stacks) > 1) {
            throw new \Exception("Found more than one matching stack for '$stackName'.");
        }
        return end($stacks);
    }

    /**
     * @param bool $fresh
     * @param null $nameFilter
     * @param null $statusFilter
     * @return Stack[]
     * @throws \Exception
     */
    public function getStacksFromApi($fresh=false, $nameFilter=null, $statusFilter=null)
    {
        if ($fresh || is_null($this->stacksCache)) {
            $this->stacksCache = [];
            $nextToken = '';
            do {
                $res = $this->cfnClient->describeStacks($nextToken ? ['NextToken' => $nextToken] : null);
                foreach ($res->get('Stacks') as $stack) {
                    $this->stacksCache[$stack['StackName']] = new Stack($stack, $this->cfnClient);
                }
                $nextToken = $res->get('NextToken');
            } while ($nextToken);
        }

        $stacks = $this->stacksCache;
        ksort($stacks);

        if (is_null($nameFilter)) {
            if ($filter = getenv('STACKFORMATION_NAME_FILTER')) {
                $nameFilter = $filter;
            }
        }

        // filter names
        if (!is_null($nameFilter)) {
            foreach (array_keys($stacks) as $stackName) {
                if (!preg_match($nameFilter, $stackName)) {
                    unset($stacks[$stackName]);
                }
            }
        }

        // filter status
        if (!is_null($statusFilter)) {
            foreach ($stacks as $stackName => $stack) { /* @var $stack Stack */
                if (!preg_match($statusFilter, $stack->getStatus())) {
                    unset($stacks[$stackName]);
                }
            }
        }

        return $stacks;
    }

}