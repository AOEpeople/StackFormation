<?php

namespace StackFormation;

use Aws\CloudFormation\Exception\CloudFormationException;
use StackFormation\Exception\StackNotFoundException;
use StackFormation\Helper\Exception;
use StackFormation\Helper\Validator;
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


    protected function executeScript($script, $envVars=[], $type)
    {
        if (empty($script)) {
            return;
        }
        if (!is_string($script)) {
            throw new \InvalidArgumentException('Script must be a string');
        }

        if ($this->output && !$this->output->isQuiet()) { $this->output->writeln("Running scripts ($type)"); }
        foreach($this->blueprint->getParameters() as $parameter) {
            $envVars[] = $parameter['ParameterKey'] . '=' . $parameter['ParameterValue'];
        }
        $envVars = array_merge([
            "BLUEPRINT=".$this->blueprint->getName(),
            "STACKNAME=".$this->blueprint->getStackName(),
            "CWD=".CWD,
        ], $envVars);
        if ($this->blueprint->getProfile()) {
            $envVars = array_merge($envVars, $this->profileManager->getEnvVarsFromProfile($this->blueprint->getProfile()));
        }

        $basePath = $this->blueprint->getBasePath();

        $tmpfile = tempnam(sys_get_temp_dir(), 'script_');
        file_put_contents($tmpfile, $script);

        $command = "cd $basePath && " . implode(' ', $envVars) . " /usr/bin/env bash -ex $tmpfile";
        passthru($command, $returnVar);
        unlink($tmpfile);
        if ($returnVar !== 0) {
            throw new \Exception('Error executing script');
        }

    }

    public function executeBeforeScript()
    {
        $script = $this->blueprint->getBeforeScript();
        $this->executeScript($script, [], 'before');
    }

    public function executeAfterScript($status)
    {
        $script = $this->blueprint->getAfterScript();
        $this->executeScript($script, ["STATUS=$status"], 'after');
    }

    /**
     * @return \Aws\Result
     * @throws \Exception
     */
    public function getChangeSet()
    {
        $arguments = $this->prepareArguments();

        try {
            $this->executeBeforeScript();

            if (isset($arguments['StackPolicyBody'])) {
                unset($arguments['StackPolicyBody']);
            }
            $arguments['ChangeSetName'] = 'stackformation' . time();

            $res = $this->getCfnClient()->createChangeSet($arguments);
            $changeSetId = $res->get('Id');

            $result = Poller::poll(function () use ($changeSetId) {
                $result = $this->getCfnClient()->describeChangeSet(['ChangeSetName' => $changeSetId]);
                if ($this->output && !$this->output->isQuiet()) { $this->output->writeln("Status: {$result['Status']}"); }
                if ($result['Status'] == 'FAILED') {
                    throw new \Exception($result['StatusReason']);
                }
                return ($result['Status'] != 'CREATE_COMPLETE') ? false : $result;
            });
        } catch (CloudFormationException $e) {
            throw Exception::refineException($e); // will try to create a StackNotFoundException
        }
        return $result;
    }

    public function deploy($dryRun=false, $force=false)
    {
        $arguments = $this->prepareArguments($force);

        if (!$dryRun) {
            $this->executeBeforeScript();
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

    public function updateStackPolicy()
    {
        $result = $this->getCfnClient()->setStackPolicy([
            'StackName' => $this->blueprint->getStackName(),
            'StackPolicyBody' => $this->blueprint->getStackPolicy()
        ]);
    }

    protected function prepareArguments($force=false)
    {
        if ($this->output && !$this->output->isQuiet()) { $this->output->write("Preparing parameters... "); }
        $parameters = $this->blueprint->getParameters();
        if ($this->output && !$this->output->isQuiet()) { $this->output->writeln("done."); }

        if ($this->output && !$this->output->isQuiet()) { $this->output->write("Preparing template... "); }
        $template = $this->blueprint->getPreprocessedTemplate($force);
        if ($this->output && !$this->output->isQuiet()) { $this->output->writeln("done."); }

        $arguments = [
            'StackName' => $this->blueprint->getStackName(),
            'Parameters' => $parameters,
            'TemplateBody' => $template,
            'Tags' => $this->blueprint->getTags()
        ];
        if ($capabilities = $this->blueprint->getCapabilities()) {
            $arguments['Capabilities'] = $capabilities;
        }
        if ($policy = $this->blueprint->getStackPolicy()) {
            $arguments['StackPolicyBody'] = $policy;
        }

        Validator::validateTags($arguments['Tags']);

        return $arguments;
    }

}
