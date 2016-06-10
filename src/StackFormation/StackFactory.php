<?php

namespace StackFormation;

class StackFactory {


    /**
     * @return \Aws\CloudFormation\CloudFormationClient
     */
    protected function getCfnClient()
    {
        return SdkFactory::getCfnClient();
    }


    public function getStack($stackName)
    {
        $stackName = $this->resolveWildcard($stackName);
        return new Stack($stackName, $this->getCfnClient());
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

    public function getStacksFromApi($fresh = false, $nameFilter=null, $statusFilter=null)
    {
        $stacks = StaticCache::get('stacks-from-api', function () {
            $res = $this->getCfnClient()->listStacks([
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
                $stacks[$stack['StackName']] = ['Status' => $stack['StackStatus']];
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
            foreach ($stacks as $key => $info) {
                if (!preg_match($nameFilter, $key)) {
                    unset($stacks[$key]);
                }
            }
        }

        // filter status
        if (!is_null($statusFilter)) {
            foreach ($stacks as $key => $info) {
                if (!preg_match($statusFilter, $info['Status'])) {
                    unset($stacks[$key]);
                }
            }
        }

        return $stacks;
    }

}