<?php

namespace StackFormation;

class Stack {

    protected $name;

    protected $parametersCache = null;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getParameter($parameter)
    {
        $parameters = $this->getParameters();
        if (!isset($parameters[$parameter])) {
            throw new \Exception("Parameter '$parameter' not found in stack '{$this->name}'");
        }
        if ($parameters[$parameter] == '****') {
            throw new \Exception("Trying to retrieve a 'NoEcho' value (Key: '$parameter')");
        }
        return $parameters[$parameter];
    }

    /**
     * Get parameter values
     *
     * @throws \Exception
     */
    public function getParameters()
    {
        if (!isset($this->parametersCache)) {
            $res = $this->getCfnClient()->describeStacks(['StackName' => $this->name]);
            $this->parametersCache = [];
            $res = $res->search('Stacks[0].Parameters');
            if (is_array($res)) {
                foreach ($res as $parameter) {
                    $this->parametersCache[$parameter['ParameterKey']] = $parameter['ParameterValue'];
                }
            }
        }
        return $this->parametersCache;
    }

    /**
     * @return \Aws\CloudFormation\CloudFormationClient
     */
    protected function getCfnClient()
    {
        return SdkFactory::getCfnClient();
    }



}