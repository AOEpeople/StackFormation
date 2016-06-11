<?php

namespace StackFormation;

use StackFormation\Exception\StackNotFoundException;

class StackFactory {

    protected $cfnClient;

    public function __construct(\Aws\CloudFormation\CloudFormationClient $cfnClient)
    {
        $this->cfnClient = $cfnClient;
    }

    /**
     * @param $stackName
     * @return Stack $stack
     * @throws \Exception
     */
    public function getStack($stackName)
    {
        $stackName = $this->resolveWildcard($stackName);
        $stacksFromApi = $this->getStacksFromApi(true);
        if (!isset($stacksFromApi[$stackName])) {
            throw new StackNotFoundException("Stack $stackName not found.");
        }
        return $stacksFromApi[$stackName];
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
        $stacks = $helper->find($stackName, array_keys($this->getStacksFromApi()));

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
        $stacks = StaticCache::get('stacks-from-api', function () {
            $res = $this->cfnClient->listStacks([
                'StackStatusFilter' => [
                    'CREATE_IN_PROGRESS',
                    'CREATE_FAILED',
                    'CREATE_COMPLETE',
                    'ROLLBACK_IN_PROGRESS',
                    'ROLLBACK_FAILED',
                    'ROLLBACK_COMPLETE',
                    'DELETE_IN_PROGRESS',
                    'DELETE_FAILED',
                    'UPDATE_IN_PROGRESS',
                    'UPDATE_COMPLETE_CLEANUP_IN_PROGRESS',
                    'UPDATE_COMPLETE',
                    'UPDATE_ROLLBACK_IN_PROGRESS',
                    'UPDATE_ROLLBACK_FAILED',
                    'UPDATE_ROLLBACK_COMPLETE_CLEANUP_IN_PROGRESS',
                    'UPDATE_ROLLBACK_COMPLETE',
                ]]
            );
            $stacks = [];
            foreach ($res->search('StackSummaries[]') as $stack) {
                $stacks[$stack['StackName']] = new Stack($stack['StackName'], $stack['StackStatus'], $this->cfnClient);
            }
            return $stacks;
        }, $fresh);

        if (is_null($nameFilter)) {
            if ($filter = getenv('STACKFORMATION_NAME_FILTER')) {
                $nameFilter = $filter;
            }
        }

        ksort($stacks);

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