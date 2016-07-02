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


    public function executeBeforeScripts()
    {
        $scripts = $this->blueprint->getBeforeScripts();
        if (count($scripts) == 0) {
            return;
        }

        if ($this->output && !$this->output->isQuiet()) { $this->output->writeln("Running scripts:"); }

        $envVars = $this->profileManager->getEnvVarsFromProfile($this->blueprint->getProfile());
        if (empty($envVars)) {
            $envVars = [];
        }

        $basePath = $this->blueprint->getBasePath();

        $tmpfile = tempnam(sys_get_temp_dir(), 'before_scripts_');
        file_put_contents($tmpfile, implode("\n", $scripts));

        $command = "cd $basePath && " . implode(' ', $envVars) . " /usr/bin/env bash -x $tmpfile";
        passthru($command, $returnVar);
        unlink($tmpfile);
        if ($returnVar !== 0) {
            throw new \Exception('Error executing commands');
        }
    }
    /**
     * @return \Aws\Result
     * @throws \Exception
     */
    public function getChangeSet()
    {
        $arguments = $this->prepareArguments();

        try {
            $this->executeBeforeScripts();

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

    public function deploy($dryRun=false)
    {
        $arguments = $this->prepareArguments();

        if (!$dryRun) {
            $this->executeBeforeScripts();
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
        if ($this->output && !$this->output->isQuiet()) { $this->output->write("Preparing parameters... "); }
        $parameters = $this->blueprint->getParameters();
        if ($this->output && !$this->output->isQuiet()) { $this->output->writeln("done."); }

        if ($this->output && !$this->output->isQuiet()) { $this->output->write("Preparing template... "); }
        $template = $this->blueprint->getPreprocessedTemplate();
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