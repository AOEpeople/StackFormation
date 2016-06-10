<?php

namespace StackFormation;

class Stack {
    
    /**
     * @var string
     */
    protected $name;

    /**
     * @var \Aws\CloudFormation\CloudFormationClient
     */
    protected $cfnClient;

    public function __construct($name, \Aws\CloudFormation\CloudFormationClient $cfnClient)
    {
        $this->name = $name;
        $this->cfnClient = $cfnClient;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getParameter($key)
    {
        $parameters = $this->getParameters();
        if (!isset($parameters[$key])) {
            throw new \Exception("Parameter '$key' not found in stack '{$this->name}'");
        }
        if ($parameters[$key] == '****') {
            throw new \Exception("Trying to retrieve a 'NoEcho' value (Key: '$key')");
        }
        return $parameters[$key];
    }

    /**
     * Get parameter values
     *
     * @throws \Exception
     */
    public function getParameters()
    {
        return StaticCache::get('stack-parameters-'.$this->name, function(){
            $parameters = [];
            $res = $this->getDataFromApi();
            if (is_array($res->search('Stacks[0].Parameters'))) {
                foreach ($res as $parameter) {
                    $parameters[$parameter['ParameterKey']] = $parameter['ParameterValue'];
                }
            }
            return $parameters;
        });
    }

    protected function getDataFromApi()
    {
        return StaticCache::get('stack-'.$this->name, function() {
            return $this->cfnClient->describeStacks(['StackName' => $this->name]);
        });
    }

    public function getOutput($key)
    {
        $outputs = $this->getOutputs();
        if (!isset($outputs[$key])) {
            throw new \Exception("Output '$key' not found in stack '{$this->name}'");
        }
        if ($outputs[$key] == '****') {
            throw new \Exception("Trying to retrieve a 'NoEcho' value (Key: '$key')");
        }
        return $outputs[$key];
    }

    public function getOutputs()
    {
        return StaticCache::get('stack-outputs-' . $this->name, function () {
            $outputs = [];
            $res = $this->getDataFromApi();
            if (is_array($res->search('Stacks[0].Outputs'))) {
                foreach ($res as $output) {
                    $outputs[$output['OutputKey']] = $output['OutputValue'];
                }
            }
            return $outputs;
        });
    }

    public function getResource($key)
    {
        $resources = $this->getResources();
        if (!isset($resources[$key])) {
            throw new \Exception("LogicalResourceId '$key' not found");
        }
        return $resources[$key];
    }

    public function getResources()
    {
        return StaticCache::get('stack-resources-' . $this->name, function () {
            $resources = [];
            $res = $this->getDataFromApi();
            if (is_array($res->search('StackResources[]'))) {
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
            throw new \Exception("Tag '$key' not found in stack '{$this->name}'");
        }
        return $tags[$key];
    }

    public function getTags()
    {
        return StaticCache::get('stack-resources-' . $this->name, function () {
            $tags = [];
            $res = $this->getDataFromApi();
            if (is_array($res->search('Stacks[0].Tags'))) {
                foreach ($res as $tag) {
                    $tags[$tag['Key']] = $tag['Value'];
                }
            }
            return $tags;
        });
    }

    public function getStatus()
    {
        throw new \Exception('getStatus is not implemented yet');

        $stacksFromApi = $this->getStacksFromApi(true);
        if (isset($stacksFromApi[$stackName])) {
            return $stacksFromApi[$stackName]['Status'];
        }

        return null;

    }

    public function getEvents()
    {
        $res = $this->cfnClient->describeStackEvents(['StackName' => $this->name]);
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

    public function cancelUpdate()
    {
         $this->cfnClient->cancelUpdateStack(['StackName' => $this->name]);
        return true;
    }

    public function delete()
    {
        $this->cfnClient->deleteStack(['StackName' => $this->name]);
        return true;
    }

    public function getBlueprintName()
    {
        throw new \Exception("getBlueprintName not implemented yet");

        $tags = $this->getTags($stackName);
        if (isset($tags["stackformation:blueprint"])) {
            return base64_decode($tags["stackformation:blueprint"]);
        }
        if ($this->getConfig()->blueprintExists($stackName)) {
            return $stackName;
        }
        return null;
    }

    public function getTemplate()
    {
        $res = $this->cfnClient->getTemplate(['StackName' => $this->name]);
        return $res->get("TemplateBody");
    }

}