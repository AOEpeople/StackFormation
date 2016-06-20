<?php

namespace StackFormation;

use StackFormation\Exception\TagNotFoundException;

class Stack {
    
    /**
     * @var string
     */
    protected $data;

    /**
     * @var \Aws\CloudFormation\CloudFormationClient
     */
    protected $cfnClient;

    public function __construct($data, \Aws\CloudFormation\CloudFormationClient $cfnClient)
    {
        $this->data = $data;
        $this->cfnClient = $cfnClient;
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
            throw new \Exception("Parameter '$key' not found in stack '{$this->getName()}'");
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
            throw new \Exception("Output '$key' not found in stack '{$this->getName()}'");
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
    public function getOutputs()
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
            throw new \Exception("Resource '$key' not found in stack '{$this->getName()}'");
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
        return StaticCache::get('stack-resources-' . $this->getName(), function () {
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
        $reference = $this->getBlueprintReference();
        return $reference['Name'];
    }

    public function getUsedEnvVars()
    {
        try {
            $reference = $this->getBlueprintReference();
            unset($reference['Name']);
        } catch (TagNotFoundException $e) {
            $reference = [];
        }
        return $reference;
    }

    /**
     * @return array
     * @throws TagNotFoundException
     */
    public function getBlueprintReference()
    {
        $data = [];
        $reference = $this->getTag('stackformation:blueprint');
        $reference = base64_decode($reference);

        if (strpos($reference, 'gz:') === 0) {
            $reference = gzdecode(substr($reference, 3));
        }

        if (substr($reference, 0, 5) == 'Name=') {
            parse_str($reference, $data);
        } else {
            // old style
            $data['Name'] = $reference;
        }
        return $data;
    }

    public function getTemplate()
    {
        $res = $this->cfnClient->getTemplate(['StackName' => $this->getName()]);
        return $res->get('TemplateBody');
    }

    public function observe(
        \Symfony\Component\Console\Output\OutputInterface $output,
        \StackFormation\StackFactory $stackFactory,
        $deleteOnTerminate=false)
    {
        $observer = new Observer($this, $stackFactory, $output);
        if ($deleteOnTerminate) {
            $observer->deleteOnSignal();
        }
        return $observer->observeStackActivity();
    }

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