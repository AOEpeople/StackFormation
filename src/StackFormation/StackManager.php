<?php

namespace StackFormation;

use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

class StackManager
{

    protected $parametersCache = [];
    protected $outputsCache = [];
    protected $resourcesCache = [];
    protected $tagsCache = [];

    protected $config;

    /**
     * @return \Aws\CloudFormation\CloudFormationClient
     */
    protected function getCfnClient()
    {
        return SdkFactory::getCfnClient();
    }

    /**
     * Get parameter values for stack
     *
     * @param      $stackName
     * @param null $key
     *
     * @return mixed
     * @throws \Exception
     */
    public function getParameters($stackName, $key = null)
    {
        $stackName = $this->resolveWildcard($stackName);

        if (!isset($this->parametersCache[$stackName])) {
            $res = $this->getCfnClient()->describeStacks(['StackName' => $stackName]);
            $parameters = [];
            $res = $res->search('Stacks[0].Parameters');
            if (is_array($res)) {
                foreach ($res as $parameter) {
                    $parameters[$parameter['ParameterKey']] = $parameter['ParameterValue'];
                }
            }
            $this->parametersCache[$stackName] = $parameters;
        }
        if (!is_null($key)) {
            if (!isset($this->parametersCache[$stackName][$key])) {
                throw new \Exception("Key '$key' not found");
            }
            if ($this->parametersCache[$stackName][$key] == '****') {
                throw new \Exception("Trying to retrieve a 'NoEcho' value (Key: '$key')");
            }
            return $this->parametersCache[$stackName][$key];
        }

        return $this->parametersCache[$stackName];
    }

    /**
     * Get output values for stack
     *
     * @param      $stackName
     * @param null $key
     *
     * @return mixed
     * @throws \Exception
     */
    public function getOutputs($stackName, $key = null)
    {
        $stackName = $this->resolveWildcard($stackName);

        if (!isset($this->outputsCache[$stackName])) {
            $res = $this->getCfnClient()->describeStacks(['StackName' => $stackName]);
            $outputs = [];
            $res = $res->search('Stacks[0].Outputs');
            if (is_array($res)) {
                foreach ($res as $output) {
                    $outputs[$output['OutputKey']] = $output['OutputValue'];
                }
            }
            $this->outputsCache[$stackName] = $outputs;
        }
        if (!is_null($key)) {
            if (!isset($this->outputsCache[$stackName][$key])) {
                throw new \Exception("Key '$key' not found");
            }
            if ($this->outputsCache[$stackName][$key] == '****') {
                throw new \Exception("Trying to retrieve a 'NoEcho' value (Key: '$key')");
            }
            return $this->outputsCache[$stackName][$key];
        }

        return $this->outputsCache[$stackName];
    }

    /**
     * Get output values for stack
     *
     * @param      $stackName
     * @param null $key
     *
     * @return mixed
     * @throws \Exception
     */
    public function getTags($stackName, $key = null)
    {
        $stackName = $this->resolveWildcard($stackName);

        if (!isset($this->tagsCache[$stackName])) {
            $res = $this->getCfnClient()->describeStacks(['StackName' => $stackName]);
            $outputs = [];
            $res = $res->search('Stacks[0].Tags');
            if (is_array($res)) {
                foreach ($res as $output) {
                    $outputs[$output['Key']] = $output['Value'];
                }
            }
            $this->tagsCache[$stackName] = $outputs;
        }
        if (!is_null($key)) {
            if (!isset($this->tagsCache[$stackName][$key])) {
                throw new \Exception("Key '$key' not found");
            }

            return $this->tagsCache[$stackName][$key];
        }

        return $this->tagsCache[$stackName];
    }

    /**
     * Get output values for stack
     *
     * @param      $stackName
     * @param null $LogicalResourceId
     *
     * @return mixed
     * @throws \Exception
     */
    public function getResources($stackName, $LogicalResourceId = null)
    {
        $stackName = $this->resolveWildcard($stackName);

        if (!isset($this->resourcesCache[$stackName])) {

            $res = $this->getCfnClient()->describeStackResources(['StackName' => $stackName]);
            $resources = [];
            foreach ($res->search('StackResources[]') as $resource) {
                $resources[$resource['LogicalResourceId']] = $resource['PhysicalResourceId'];
            }
            $this->resourcesCache[$stackName] = $resources;
        }
        if (!is_null($LogicalResourceId)) {
            if (!isset($this->resourcesCache[$stackName][$LogicalResourceId])) {
                throw new \Exception("LogicalResourceId '$LogicalResourceId' not found");
            }

            return $this->resourcesCache[$stackName][$LogicalResourceId];
        }

        return $this->resourcesCache[$stackName];
    }

    /**
     * Resolve wildcard
     *
     * @param $stackName
     * @return mixed
     * @throws \Exception
     */
    protected function resolveWildcard($stackName)
    {
        if (strpos($stackName, '*') === false) {
            return $stackName;
        }

        $helper = new \StackFormation\Helper();
        $stacks = $helper->find($stackName, array_keys($this->getStacksFromApi()));

        if (count($stacks) == 0) {
            throw new \Exception("No matching stack found for '$stackName'");
        } elseif (count($stacks) > 1) {
            throw new \Exception("Found more than one matching stack for '$stackName'.");
        }
        return end($stacks);
    }

    /**
     * @return array
     */
    public function getStacksFromApi($fresh = false, $nameFilter=null, $statusFilter=null)
    {
        $that = $this;
        $stacks = StaticCache::get('stacks-from-api', function () use ($that) {
            $res = $that->getCfnClient()->listStacks(
                [
                    'StackStatusFilter' => [
                        'CREATE_IN_PROGRESS',
                        'CREATE_FAILED',
                        'CREATE_COMPLETE',
                        'ROLLBACK_IN_PROGRESS',
                        'ROLLBACK_FAILED',
                        'ROLLBACK_COMPLETE',
                        'DELETE_IN_PROGRESS',
                        'DELETE_FAILED',
                        'UPDATE_IN_PROGRESS',
                        'UPDATE_COMPLETE_CLEANUP_IN_PROGRESS',
                        'UPDATE_COMPLETE',
                        'UPDATE_ROLLBACK_IN_PROGRESS',
                        'UPDATE_ROLLBACK_FAILED',
                        'UPDATE_ROLLBACK_COMPLETE_CLEANUP_IN_PROGRESS',
                        'UPDATE_ROLLBACK_COMPLETE',
                    ],
                ]
            );
            $stacks = [];
            foreach ($res->search('StackSummaries[]') as $stack) {
                $stacks[$stack['StackName']] = ['Status' => $stack['StackStatus']];
            }

            return $stacks;
        }, $fresh);

        if (is_null($nameFilter)) {
            if ($filter = getenv('STACKFORMATION_NAME_FILTER')) {
                $nameFilter = $filter;
            }
        }

        ksort($stacks);

        // filter names
        if (!is_null($nameFilter)) {
            foreach ($stacks as $key => $info) {
                if (!preg_match($nameFilter, $key)) {
                    unset($stacks[$key]);
                }
            }
        }

        // filter status
        if (!is_null($statusFilter)) {
            foreach ($stacks as $key => $info) {
                if (!preg_match($statusFilter, $info['Status'])) {
                    unset($stacks[$key]);
                }
            }
        }

        return $stacks;
    }


    public function deleteStack($stackName)
    {
        $this->getCfnClient()->deleteStack(['StackName' => $stackName]);
    }

    public function validateTemplate($stackName)
    {
        $res = $this->getCfnClient()->validateTemplate([
            'TemplateBody' => $this->getPreprocessedTemplate($stackName)
        ]);

        // will throw an exception if there's a problem
    }

    public function getPreprocessedTemplate($stackName)
    {
        $stackConfig = $this->getConfig()->getStackConfig($stackName);

        if (empty($stackConfig['template']) || !is_array($stackConfig['template'])) {
            throw new \Exception('No template(s) found');
        }

        $preProcessor = new Preprocessor();

        $templateContents = [];
        foreach ($stackConfig['template'] as $key => $template) {
            $templateContents[$key] = $preProcessor->process($template);
        }

        $templateMerger = new TemplateMerger();
        $description = !empty($stackConfig['description']) ? $stackConfig['description'] : null;
        return $templateMerger->merge($templateContents, $description);
    }

    public function getTemplate($stackName)
    {
        $stackName = $this->resolveWildcard($stackName);

        $res = $this->getCfnClient()->getTemplate(['StackName' => $stackName]);

        return $res->get("TemplateBody");
    }

    /**
     * Update stack
     *
     * @param string $stackName
     * @param bool $dryRun
     * @throws \Exception
     */
    public function deployStack($stackName, $dryRun = false)
    {
        $stackConfig = $this->getConfig()->getStackConfig($stackName);

        if (isset($stackConfig['profile'])) {
            $profileManager = new \AwsInspector\ProfileManager();
            $profileManager->loadProfile($stackConfig['profile']);
            echo "Loading Profile: " . $stackConfig['profile'] . "\n";
        }

        $effectiveStackName = $this->getConfig()->getEffectiveStackName($stackName);

        $arguments = [
            'StackName' => $effectiveStackName,
            'Parameters' => $this->getParametersFromConfig($stackName),
            'TemplateBody' => $this->getPreprocessedTemplate($stackName),
        ];

        if (isset($stackConfig['before']) && is_array($stackConfig['before']) && count($stackConfig['before']) > 0) {
            $this->executeScripts($stackConfig['before'], $stackConfig['basepath'], $stackName);
        }

        if (isset($stackConfig['Capabilities'])) {
            $arguments['Capabilities'] = explode(',', $stackConfig['Capabilities']);
        }

        if (isset($stackConfig['stackPolicy'])) {
            if (!is_file($stackConfig['stackPolicy'])) {
                throw new \Exception('Stack policy "' . $stackConfig['stackPolicy'] . '" not found', 1452687982);
            }
            $arguments['StackPolicyBody'] = file_get_contents($stackConfig['stackPolicy']);
        }

        $stackStatus = $this->getStackStatus($effectiveStackName);
        if (strpos($stackName, 'IN_PROGRESS') !== false) {
            throw new \Exception("Stack can't be updated right now. Status: $stackStatus");
        } elseif (!empty($stackStatus) && $stackStatus != 'DELETE_COMPLETE') {
            if (!$dryRun) {
                $this->getCfnClient()->updateStack($arguments);
            }
        } else {
            $arguments['Tags'] = $this->getConfig()->getStackTags($stackName);

            $onFailure = isset($stackConfig['OnFailure']) ? $stackConfig['OnFailure'] : 'DO_NOTHING';
            if (!in_array($onFailure, ['ROLLBACK', 'DO_NOTHING', 'DELETE'])) {
                throw new \InvalidArgumentException("Invalid value for onFailure parameter");
            }

            $arguments['OnFailure'] = $onFailure;
            if (!$dryRun) {
                $this->getCfnClient()->createStack($arguments);
            }
        }
    }

    protected function executeScripts(array $scripts, $path, $stackName = null)
    {
        $cwd = getcwd();
        chdir($path);

        foreach ($scripts as &$script) {
            $script = $this->resolvePlaceholders($script, $stackName);
        }
        passthru(implode("\n", $scripts), $returnVar);
        if ($returnVar !== 0) {
            throw new \Exception('Error executing commands');
        }
        chdir($cwd);
    }

    public function observeStackActivity($stackName, OutputInterface $output, $pollInterval = 10, $deleteOnSignal=false)
    {
        if ($deleteOnSignal) {
            $terminator = new Terminator($stackName, $this, $output);
            $terminator->setupSignalHandler();
        }

        $returnValue = 0;
        $printedEvents = [];
        $first = true;
        do {
            if ($first) {
                $first = false;
            } else {
                sleep($pollInterval);
            }
            $status = $this->getStackStatus($stackName);

            $output->writeln("-> Polling... (Stack Status: $status)");

            $events = $this->describeStackEvents($stackName);

            $rows = [];
            foreach ($events as $eventId => $event) {
                if (!in_array($eventId, $printedEvents)) {
                    $printedEvents[] = $eventId;
                    $rows[] = [
                        // $event['Timestamp'],
                        $this->decorateStatus($event['Status']),
                        $event['ResourceType'],
                        $event['LogicalResourceId'],
                        wordwrap($event['ResourceStatusReason'], 40, "\n"),
                    ];
                }
            }

            $table = new Table($output);
            $table->setRows($rows);
            $table->render();
        } while (strpos($status, 'IN_PROGRESS') !== false);

        $formatter = new FormatterHelper();
        if (strpos($status, 'FAILED') !== false) {
            $formattedBlock = $formatter->formatBlock(['Error!', 'Status: ' . $status], 'error', true);
        } else {
            $formattedBlock = $formatter->formatBlock(['Completed', 'Status: ' . $status], 'info', true);
        }

        if (!in_array($status, ['CREATE_COMPLETE', 'UPDATE_COMPLETE'])) {
            $returnValue = 1;
        }

        $output->writeln("\n\n$formattedBlock\n\n");

        $output->writeln("== OUTPUTS ==");
        try {
            $outputs = $this->getOutputs($stackName);

            $rows = [];
            foreach ($outputs as $key => $value) {
                $value = strlen($value) > 100 ? substr($value, 0, 100) . "..." : $value;
                $rows[] = [$key, $value];
            }

            $table = new Table($output);
            $table
                ->setHeaders(['Key', 'Value'])
                ->setRows($rows);
            $table->render();
        } catch (\Exception $e) {
            // never mind...
        }

        return $returnValue;
    }

    protected function decorateStatus($status)
    {
        if (strpos($status, 'IN_PROGRESS') !== false) {
            return "<fg=yellow>$status</>";
        }
        if (strpos($status, 'COMPLETE') !== false) {
            return "<fg=green>$status</>";
        }
        if (strpos($status, 'FAILED') !== false) {
            return "<fg=red>$status</>";
        }

        return $status;
    }

    public function getStackStatus($stackName)
    {
        $stacksFromApi = $this->getStacksFromApi(true);
        if (isset($stacksFromApi[$stackName])) {
            return $stacksFromApi[$stackName]['Status'];
        }

        return null;
    }

    public function describeStackEvents($stackName)
    {
        $res = $this->getCfnClient()->describeStackEvents(['StackName' => $stackName]);
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

    public function resolvePlaceholders($string, $stackName = null)
    {
        $vars = $stackName ? $this->getConfig()->getStackVars($stackName) : $this->getConfig()->getGlobalVars();

        $originalString = $string;

        // {var:...}
        $string = preg_replace_callback(
            '/\{var:([^:\}]+?)\}/',
            function ($matches) use ($vars) {
                if (!isset($vars[$matches[1]])) {
                    throw new \Exception("Variable '{$matches[1]}' not found");
                }

                return $vars[$matches[1]];
            },
            $string
        );

        // {var:...}
        static $time;
        if (!isset($time)) {
            $time = time();
        }
        $string = preg_replace('/\{tstamp}/', $time, $string);

        // {env:...}
        $string = preg_replace_callback(
            '/\{env:([^:\}]+?)\}/',
            function ($matches) {
                if (!getenv($matches[1])) {
                    throw new \Exception("Environment variable '{$matches[1]}' not found");
                }

                return getenv($matches[1]);
            },
            $string
        );

        // {env:...:...} (with default value if env var is not set)
        $string = preg_replace_callback(
            '/\{env:(.*?):(.*?)\}/',
            function ($matches) {
                if (!getenv($matches[1])) {
                    return $matches[2];
                }

                return getenv($matches[1]);
            },
            $string
        );

        // {output:...:...}
        $string = preg_replace_callback(
            '/\{output:(.*?):(.*?)\}/',
            function ($matches) {
                return $this->getOutputs($matches[1], $matches[2]);
            },
            $string
        );

        // {resource:...:...}
        $string = preg_replace_callback(
            '/\{resource:(.*?):(.*?)\}/',
            function ($matches) {
                return $this->getResources($matches[1], $matches[2]);
            },
            $string
        );

        // {parameter:...:...}
        $string = preg_replace_callback(
            '/\{parameter:(.*?):(.*?)\}/',
            function ($matches) {
                return $this->getParameters($matches[1], $matches[2]);
            },
            $string
        );

        // recursively continue until everything is replaced
        if ($string != $originalString) {
            $string = $this->resolvePlaceholders($string, $stackName);
        }

        return $string;
    }

    public function getParametersFromConfig($stackName, $resolvePlaceholders = true, $flatten = false)
    {

        $stackConfig = $this->getConfig()->getStackConfig($stackName);

        $parameters = [];

        if (isset($stackConfig['parameters'])) {
            foreach ($stackConfig['parameters'] as $parameterKey => $parameterValue) {
                $tmp = ['ParameterKey' => $parameterKey];
                if (is_null($parameterValue)) {
                    $tmp['UsePreviousValue'] = true;
                } else {
                    $tmp['ParameterValue'] = $resolvePlaceholders ? $this->resolvePlaceholders($parameterValue, $stackName) : $parameterValue;
                }
                if (strpos($tmp['ParameterKey'], '*') !== false) {
                    $count = 0;
                    foreach (array_keys($stackConfig['template']) as $key) {
                        if (!is_int($key)) {
                            $count++;
                            $newParameter = $tmp;
                            $newParameter['ParameterKey'] = str_replace('*', $key, $tmp['ParameterKey']);
                            $parameters[] = $newParameter;
                        }
                    }
                    if ($count == 0) {
                        throw new \Exception('Found placeholder \'*\' in parameter key but the templates don\'t use prefixes');
                    }
                } else {
                    $parameters[] = $tmp;
                }
            }
        }


        if ($flatten) {
            $tmp = [];
            foreach ($parameters as $parameter) {
                $tmp[$parameter['ParameterKey']] = $parameter['ParameterValue'];
            }
            return $tmp;
        }

        return $parameters;
    }

    public function getConfig()
    {
        if (is_null($this->config)) {
            $this->config = new Config();
        }

        return $this->config;
    }
}
