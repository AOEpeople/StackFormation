<?php

namespace StackFormation;

use Aws\CloudFormation\Exception\CloudFormationException;
use StackFormation\Exception\StackNotFoundException;
use Symfony\Component\Console\Output\OutputInterface;

class BlueprintAction {
    
    protected $cfnClient;
    protected $blueprint;
    protected $profileManager;
    protected $output;

    public function __construct(
        Blueprint $blueprint,
        \StackFormation\Profile\Manager $profileManager,
        OutputInterface $output=null
    )
    {
        $this->blueprint = $blueprint;
        $this->profileManager = $profileManager;
        $this->output = $output;
    }

    /**
     * @return \Aws\CloudFormation\CloudFormationClient
     */
    protected function getCfnClient()
    {
        if (is_null($this->cfnClient)) {
            $this->cfnClient = $this->profileManager->getClient('CloudFormation', $this->blueprint->getProfile());
        }
        return $this->cfnClient;
    }

    public function validateTemplate()
    {
        $this->getCfnClient()->validateTemplate(['TemplateBody' => $this->blueprint->getPreprocessedTemplate()]);
        // will throw an exception if there's a problem
    }

    /**
     * @return \Aws\Result
     * @throws \Exception
     */
    public function getChangeSet()
    {
        $arguments = $this->prepareArguments();

        try {
            $this->blueprint->executeBeforeScripts();

            if (isset($arguments['StackPolicyBody'])) {
                unset($arguments['StackPolicyBody']);
            }
            $arguments['ChangeSetName'] = 'stackformation' . time();

            $res = $this->getCfnClient()->createChangeSet($arguments);
            $changeSetId = $res->get('Id');

            $result = Poller::poll(function () use ($changeSetId) {
                $result = $this->getCfnClient()->describeChangeSet(['ChangeSetName' => $changeSetId]);
                if ($this->output && !$this->output->isQuiet()) {
                    $this->output->writeln("Status: {$result['Status']}");
                }
                if ($result['Status'] == 'FAILED') {
                    throw new \Exception($result['StatusReason']);
                }
                return ($result['Status'] != 'CREATE_COMPLETE') ? false : $result;
            });
        } catch (CloudFormationException $e) {
            throw Helper::refineException($e); // will try to create a StackNotFoundException
        }
        return $result;
    }

    public function deploy($dryRun=false)
    {
        $arguments = $this->prepareArguments();

        if (!$dryRun) {
            $this->blueprint->executeBeforeScripts();
        }

        try {
            $stackFactory = $this->profileManager->getStackFactory($this->blueprint->getProfile());
            $stackStatus = $stackFactory->getStackStatus($this->blueprint->getStackName());
        } catch (StackNotFoundException $e) {
            $stackStatus = false;
        }

        if (strpos($stackStatus, 'IN_PROGRESS') !== false) {
            throw new \Exception("Stack can't be updated right now. Status: $stackStatus");
        } elseif (!empty($stackStatus) && $stackStatus != 'DELETE_COMPLETE') {
            if (!$dryRun) {
                $this->getCfnClient()->updateStack($arguments);
            }
        } else {
            $arguments['OnFailure'] = $this->blueprint->getOnFailure();
            if (!$dryRun) {
                $this->getCfnClient()->createStack($arguments);
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
        try {
            $arguments['Tags'][] = [
                'Key' => 'stackformation:blueprint',
                'Value' => $this->blueprint->getBlueprintReference()
            ];
        } catch (\Exception $e) {
            // TODO: ignoring this for now...
        }

        Helper::validateTags($arguments['Tags']);

        return $arguments;
    }

}