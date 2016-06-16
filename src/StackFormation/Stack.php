<?php

namespace StackFormation;

class Stack {
    
    /**
     * @var string
     */
    protected $name;
    protected $status;
    protected $data;

    /**
     * @var \Aws\CloudFormation\CloudFormationClient
     */
    protected $cfnClient;

    public function __construct($name, $data, \Aws\CloudFormation\CloudFormationClient $cfnClient)
    {
        $this->name = $name;
        $this->cfnClient = $cfnClient;
        $this->data = $data;
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
        //return StaticCache::get('stack-parameters-'.$this->name, function(){
            $parameters = [];
            $res = $this->data['Parameters'];
            if (is_array($res)) {
                foreach ($res as $parameter) {
                    $parameters[$parameter['ParameterKey']] = $parameter['ParameterValue'];
                }
            }
            return $parameters;
        //});
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
        //return StaticCache::get('stack-outputs-' . $this->name, function () {
            $outputs = [];
            $res = isset($this->data['Outputs']) ? $this->data['Outputs'] : [];
            foreach ($res as $output) {
                $outputs[$output['OutputKey']] = $output['OutputValue'];
            }
            return $outputs;
        //});
    }

    public function getResource($key)
    {
        $resources = $this->getResources();
        if (!isset($resources[$key])) {
            throw new \Exception("Resource '$key' not found in stack '{$this->name}'");
        }
        return $resources[$key];
    }

    public function getResources()
    {
        return StaticCache::get('stack-resources-' . $this->name, function () {
            $resources = [];
            $res = $this->cfnClient->describeStackResources(['StackName' => $this->name])->search('StackResources[]');
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
            throw new \Exception("Tag '$key' not found in stack '{$this->name}'");
        }
        return $tags[$key];
    }

    public function getTags()
    {
        //return StaticCache::get('stack-tags-' . $this->name, function () {
            $tags = [];
            $res = $this->data['Tags'];
            if (is_array($res)) {
                foreach ($res as $tag) {
                    $tags[$tag['Key']] = $tag['Value'];
                }
            }
            return $tags;
        //});
    }

    public function getStatus()
    {
        return $this->data['StackStatus'];
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
        return $this;
    }

    public function delete()
    {
        $this->cfnClient->deleteStack(['StackName' => $this->name]);
        return $this;
    }

    public function getBlueprintName()
    {
        $reference = $this->getBlueprintReference();
        return $reference['Name'];
    }

    public function getUsedEnvVars()
    {
        $reference = $this->getBlueprintReference();
        unset($reference['Name']);
        return $reference;
    }

    public function getBlueprintReference()
    {
        $data = [];
        $reference = $this->getTag('stackformation:blueprint');
        $reference = base64_decode($reference);

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
        echo "Get Template {$this->name}\n";
        $res = $this->cfnClient->getTemplate(['StackName' => $this->name]);
        return $res->get("TemplateBody");
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

}