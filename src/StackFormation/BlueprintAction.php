<?php

namespace StackFormation;

use StackFormation\Exception\StackNotFoundException;

class BlueprintAction {
    
    protected $cfnClient;
    protected $blueprint;

    public function __construct(Blueprint $blueprint, \Aws\CloudFormation\CloudFormationClient $cfnClient)
    {
        $this->blueprint = $blueprint;
        $this->cfnClient = $cfnClient;
    }

    public function validateTemplate()
    {
        $this->cfnClient->validateTemplate(['TemplateBody' => $this->blueprint->getPreprocessedTemplate()]);
        // will throw an exception if there's a problem
    }

    public function getChangeSet($verbose=true)
    {
        $arguments = $this->prepareArguments();
        if (isset($arguments['StackPolicyBody'])) {
            unset($arguments['StackPolicyBody']);
        }
        $arguments['ChangeSetName'] = 'stackformation' . time();

        $res = $this->cfnClient->createChangeSet($arguments);
        $changeSetId = $res->get('Id');
        $result = Poller::poll(function() use ($changeSetId, $verbose) {
            $result = $this->cfnClient->describeChangeSet(['ChangeSetName' => $changeSetId]);
            if ($verbose) {
                echo "Status: {$result['Status']}\n";
            }
            if ($result['Status'] == 'FAILED') {
                throw new \Exception($result['StatusReason']);
            }
            return ($result['Status'] != 'CREATE_COMPLETE') ? false : $result;
        });
        return $result;
    }

    public function deploy($dryRun=false, StackFactory $stackFactory)
    {
        $arguments = $this->prepareArguments();

        try {
            $stackStatus = $stackFactory->getStack($this->blueprint->getStackName())->getStatus();
        } catch (StackNotFoundException $e) {
            $stackStatus = false;
        }

        if (strpos($stackStatus, 'IN_PROGRESS') !== false) {
            throw new \Exception("Stack can't be updated right now. Status: $stackStatus");
        } elseif (!empty($stackStatus) && $stackStatus != 'DELETE_COMPLETE') {
            if (!$dryRun) {
                $this->cfnClient->updateStack($arguments);
            }
        } else {
            $arguments['OnFailure'] = $this->blueprint->getOnFailure();
            if (!$dryRun) {
                $this->cfnClient->createStack($arguments);
            }
        }
    }

    protected function prepareArguments()
    {
        $arguments = [
            'StackName' => $this->blueprint->getStackName(),
            'Parameters' => $this->blueprint->getParameters(),
            'TemplateBody' => $this->blueprint->getPreprocessedTemplate(),
            'Tags' => $this->blueprint->getTags()
        ];
        if ($capabilities = $this->blueprint->getCapabilities()) {
            $arguments['Capabilities'] = $capabilities;
        }
        if ($policy = $this->blueprint->getStackPolicy()) {
            $arguments['StackPolicyBody'] = $policy;
        }

        // this is how we reference a stack back to its blueprint
        $arguments['Tags'][] = [
            'Key' => 'stackformation:blueprint',
            'Value' => $this->blueprint->getBlueprintReference()
        ];

        Helper::validateTags($arguments['Tags']);

        return $arguments;
    }

}