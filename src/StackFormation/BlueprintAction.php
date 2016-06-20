<?php

namespace StackFormation;

use StackFormation\Exception\StackNotFoundException;

class BlueprintAction {
    
    protected $cfnClient;

    public function __construct(\Aws\CloudFormation\CloudFormationClient $cfnClient)
    {
        $this->cfnClient = $cfnClient;
    }

    public function validateTemplate(Blueprint $blueprint)
    {
        $this->cfnClient->validateTemplate(['TemplateBody' => $blueprint->getPreprocessedTemplate()]);
        // will throw an exception if there's a problem
    }

    /**
     * @param Blueprint $blueprint
     * @param bool $verbose
     * @return \Aws\Result
     * @throws \Exception
     */
    public function getChangeSet(Blueprint $blueprint, $verbose=true)
    {
        $arguments = $this->prepareArguments($blueprint);
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

    public function deploy(Blueprint $blueprint, $dryRun=false, StackFactory $stackFactory)
    {
        $arguments = $this->prepareArguments($blueprint);

        try {
            $stackStatus = $stackFactory->getStack($blueprint->getStackName())->getStatus();
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
            $arguments['OnFailure'] = $blueprint->getOnFailure();
            if (!$dryRun) {
                $this->cfnClient->createStack($arguments);
            }
        }
    }

    protected function prepareArguments(Blueprint $blueprint)
    {
        $arguments = [
            'StackName' => $blueprint->getStackName(),
            'Parameters' => $blueprint->getParameters(),
            'TemplateBody' => $blueprint->getPreprocessedTemplate(),
            'Tags' => $blueprint->getTags()
        ];
        if ($capabilities = $blueprint->getCapabilities()) {
            $arguments['Capabilities'] = $capabilities;
        }
        if ($policy = $blueprint->getStackPolicy()) {
            $arguments['StackPolicyBody'] = $policy;
        }

        // this is how we reference a stack back to its blueprint
        try {
            $arguments['Tags'][] = [
                'Key' => 'stackformation:blueprint',
                'Value' => $blueprint->getBlueprintReference()
            ];
        } catch (\Exception $e) {
            // TODO: ignoring this for now...
        }

        Helper::validateTags($arguments['Tags']);

        return $arguments;
    }

}