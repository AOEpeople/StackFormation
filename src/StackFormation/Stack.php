<?php

namespace StackFormation;

use StackFormation\Exception\OutputNotFoundException;
use StackFormation\Exception\ParameterNotFoundException;
use StackFormation\Exception\ResourceNotFoundException;
use StackFormation\Exception\TagNotFoundException;
use StackFormation\Helper\Cache;

class Stack {

    CONST METADATA_KEY = 'StackFormation';
    CONST METADATA_KEY_BLUEPRINT = 'Blueprint';
    CONST METADATA_KEY_ENVVARS = 'EnvironmentVariables';

    /**
     * @var string
     */
    protected $data;

    /**
     * @var \Aws\CloudFormation\CloudFormationClient
     */
    protected $cfnClient;

    /**
     * @var Cache
     */
    protected $cache;

    public function __construct($data, \Aws\CloudFormation\CloudFormationClient $cfnClient)
    {
        $this->data = $data;
        $this->cfnClient = $cfnClient;
        $this->cache = new Cache();
    }

    public function getName()
    {
        return $this->data['StackName'];
    }

    public function getDescription()
    {
        return $this->data['Description'];
    }

    public function getStatus()
    {
        return $this->data['StackStatus'];
    }

    public function getParameter($key)
    {
        $parameters = $this->getParameters();
        if (!isset($parameters[$key])) {
            throw new ParameterNotFoundException("Parameter '$key' not found in stack '{$this->getName()}'");
        }
        if ($parameters[$key] == '****') {
            throw new \Exception("Trying to retrieve a 'NoEcho' value (Key: '$key')");
        }
        return $parameters[$key];
    }

    /**
     * Get parameter values
     *
     * @return array
     * @throws \Exception
     */
    public function getParameters()
    {
        $parameters = [];
        $res = isset($this->data['Parameters']) ? $this->data['Parameters'] : [];
        foreach ($res as $parameter) {
            $parameters[$parameter['ParameterKey']] = $parameter['ParameterValue'];
        }
        return $parameters;
    }

    /**
     * Get output
     *
     * @param $key
     * @return string
     * @throws \Exception
     */
    public function getOutput($key)
    {
        $outputs = $this->getOutputs();
        if (!isset($outputs[$key])) {
            throw new OutputNotFoundException("Output '$key' not found in stack '{$this->getName()}'");
        }
        if ($outputs[$key] == '****') {
            throw new \Exception("Trying to retrieve a 'NoEcho' value (Key: '$key')");
        }
        return $outputs[$key];
    }

    /**
     * Get outputs
     *
     * @return array
     */
    public function  getOutputs()
    {
        $outputs = [];
        $res = isset($this->data['Outputs']) ? $this->data['Outputs'] : [];
        foreach ($res as $output) {
            $outputs[$output['OutputKey']] = $output['OutputValue'];
        }
        return $outputs;
    }

    /**
     * Get resource
     *
     * @param $key
     * @return string
     * @throws \Exception
     */
    public function getResource($key)
    {
        $resources = $this->getResources();
        if (!isset($resources[$key])) {
            throw new ResourceNotFoundException("Resource '$key' not found in stack '{$this->getName()}'");
        }
        return $resources[$key];
    }

    /**
     * Ger resources
     *
     * @return array
     * @throws \Exception
     */
    public function getResources()
    {
        return $this->cache->get(__METHOD__, function () {
            $resources = [];
            $res = $this->cfnClient->describeStackResources(['StackName' => $this->getName()])->search('StackResources[]');
            if (is_array($res)) {
                foreach ($res as $resource) {
                    $resources[$resource['LogicalResourceId']] = isset($resource['PhysicalResourceId']) ? $resource['PhysicalResourceId'] : '';
                }
            }
            return $resources;
        });
    }

    public function getTag($key)
    {
        $tags = $this->getTags();
        if (!isset($tags[$key])) {
            throw new TagNotFoundException("Tag '$key' not found in stack '{$this->getName()}'");
        }
        return $tags[$key];
    }

    public function getTags()
    {
        $tags = [];
        $res = $this->data['Tags'];
        if (is_array($res)) {
            foreach ($res as $tag) {
                $tags[$tag['Key']] = $tag['Value'];
            }
        }
        return $tags;
    }

    /**
     * @return array
     */
    public function getEvents()
    {
        $res = $this->cfnClient->describeStackEvents(['StackName' => $this->getName()]);
        $events = [];
        foreach ($res->search('StackEvents[]') as $event) {
            $events[$event['EventId']] = [
                'Timestamp' => (string)$event['Timestamp'],
                'Status' => $event['ResourceStatus'],
                'ResourceType' => $event['ResourceType'],
                'LogicalResourceId' => $event['LogicalResourceId'],
                'ResourceStatus' => $event['ResourceStatus'],
                'ResourceStatusReason' => isset($event['ResourceStatusReason']) ? $event['ResourceStatusReason'] : '',
            ];
        }
        return array_reverse($events, true);
    }

    public function getBlueprintName()
    {
        $blueprintReference = $this->getBlueprintReference();
        if (!isset($blueprintReference[self::METADATA_KEY_BLUEPRINT])) {
            throw new \Exception('No bleprint name found in blueprint reference for stack ' . $this->getName());
        }
        return $blueprintReference[self::METADATA_KEY_BLUEPRINT];
    }

    public function getUsedEnvVars()
    {
        $blueprintReference = $this->getBlueprintReference();
        if (!isset($blueprintReference[self::METADATA_KEY_ENVVARS])) {
            throw new \Exception('No env vars found in blueprint reference for stack ' . $this->getName());
        }
        return $blueprintReference[self::METADATA_KEY_ENVVARS];
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function getBlueprintReference()
    {
        return $this->cache->get(__METHOD__, function () {
            $template = $this->getTemplate();
            $decodedTemplate = json_decode($template, true);
            if (!isset($decodedTemplate['Metadata']) || !isset($decodedTemplate['Metadata'][self::METADATA_KEY])) {
                throw new \Exception('No blueprint reference found');
            }
            return $decodedTemplate['Metadata'][self::METADATA_KEY];
        });
    }

    public function getTemplate()
    {
        return $this->cache->get(__METHOD__, function () {
            $res = $this->cfnClient->getTemplate(['StackName' => $this->getName()]);
            return $res->get('TemplateBody');
        });
    }

    ///**
    // * @param \Symfony\Component\Console\Output\OutputInterface $output
    // * @param StackFactory $stackFactory
    // * @param bool $deleteOnTerminate
    // * @return int
    // * @deprecated
    // */
    //public function observe(
    //    \Symfony\Component\Console\Output\OutputInterface $output,
    //    \StackFormation\StackFactory $stackFactory,
    //    $deleteOnTerminate=false)
    //{
    //    $observer = new Observer($this, $stackFactory, $output);
    //    if ($deleteOnTerminate) {
    //        $observer->deleteOnSignal();
    //    }
    //    return $observer->observeStackActivity();
    //}

    public function cancelUpdate()
    {
        $this->cfnClient->cancelUpdateStack(['StackName' => $this->getName()]);
        return $this;
    }

    public function delete()
    {
        $this->cfnClient->deleteStack(['StackName' => $this->getName()]);
        return $this;
    }

}