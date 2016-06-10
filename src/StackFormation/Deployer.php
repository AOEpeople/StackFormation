<?php

namespace StackFormation;


class Deployer
{
    
    

    public function __construct(Blueprint $blueprint, \Aws\CloudFormation\CloudFormationClient $cfnClient)
    {
        $this->blueprint = $blueprint;
    }

    protected function prepareArguments()
    {

        //if (isset($blueprintConfig['account'])) {
        //    $configuredAccountId = $this->resolvePlaceholders($blueprintConfig['account'], $blueprintName, 'account');
        //    if ($configuredAccountId != $this->getConfig()->getCurrentUsersAccountId()) {
        //        throw new \Exception(sprintf("Current user's AWS account id '%s' does not match the one configured in the blueprint: '%s'",
        //            $this->getConfig()->getCurrentUsersAccountId(),
        //            $configuredAccountId
        //        ));
        //    }
        //}
        $this->blueprint->enforceProfile();
        $arguments = $this->blueprint->prepareArguments();
        $this->blueprint->executeBeforeScripts();

        return $arguments;
    }


}
