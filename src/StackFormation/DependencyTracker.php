<?php

namespace StackFormation;

class DependencyTracker
{

    protected $stacks = [];
    protected $envVars = [];

    public function reset()
    {
        $this->stacks = [];
        $this->envVars = [];
    }

    public function trackEnvUsage($envVar, $withDefault=false, $value, Blueprint $sourceBlueprint, $sourceType, $sourceKey)
    {
        $type = $withDefault ? 'env_with_default' : 'env';
        if (!isset($this->envVars[$type])) {
            $this->envVars[$type] = [];
        }
        if (!isset($this->envVars[$type][$envVar])) {
            $this->envVars[$type][$envVar] = ['value' => $value, 'sources' => []];
        }
        $this->envVars[$type][$envVar]['sources'][] = [
            'blueprint' => $sourceBlueprint ? $sourceBlueprint->getName() : '',
            'type' => $sourceType ? $sourceType : '',
            'key' => $sourceKey ? $sourceKey : ''
        ];
    }

    public function trackStackDependency($type, $stack, $resource, Blueprint $sourceBlueprint, $sourceType, $sourceKey)
    {
        if (!isset($this->stacks[$type])) {
            $this->stacks[$type] = [];
        }
        if (!isset($this->stacks[$type][$stack])) {
            $this->stacks[$type][$stack] = [];
        }
        if (!isset($this->stacks[$type][$stack][$resource])) {
            $this->stacks[$type][$stack][$resource] = [];
        }
        $this->stacks[$type][$stack][$resource][] = [
            'type' => $sourceType ? $sourceType : '',
            'blueprint' => $sourceBlueprint ? $sourceBlueprint->getName() : '',
            'key' => $sourceKey ? $sourceKey : ''
        ];
    }

    public function getStackDependencies()
    {
        return $this->stacks;
    }

    public function getEnvDependencies()
    {
        return $this->envVars;
    }

    public function getUsedEnvironmentVariables()
    {
        $vars = [];
        foreach ($this->envVars as $type => $typeData) {
            foreach ($typeData as $variableName => $data) {
                $vars[$variableName] = $data['value'];
            }
        }
        return $vars;
    }

    public function getStackDependenciesAsFlatList()
    {
        $rows = [];
        foreach ($this->stacks as $type => $typeData) {
            foreach ($typeData as $stackName => $stackData) {
                foreach ($stackData as $resource => $sources) {
                    $sourcesList = [];
                    foreach ($sources as $source) {
                        unset($source['blueprint']);
                        $sourcesList[] = implode(':', $source);
                    }
                    $rows[] = [
                        implode("\n", $sourcesList),
                        $stackName,
                        $type.':'.$resource
                    ];
                }
            }
        }
        return $rows;
    }

    public function getEnvDependenciesAsFlatList()
    {
        $rows = [];
        foreach ($this->envVars as $type => $typeData) {
            foreach ($typeData as $envVar => $tmp) {
                $sourcesList = [];
                foreach ($tmp['sources'] as $source) {
                    unset($source['blueprint']);
                    $sourcesList[] = implode(':', $source);
                }
                $rows[] = [
                    implode("\n", $sourcesList),
                    $type,
                    $envVar,
                    $tmp['value']
                ];
            }
        }
        return $rows;
    }

    public function getStacks()
    {
        $stacks = [];
        foreach ($this->stacks as $type => $typeData) {
            $stacks = array_merge($stacks, array_keys($typeData));
        }
        $stacks = array_unique($stacks);
        sort($stacks);
        return $stacks;
    }

    public function findDependantsForStack($stackName)
    {
        $dependants = [];
        foreach ($this->stacks as $type => $typeData) {
            if (isset($typeData[$stackName])) {
                foreach ($typeData[$stackName] as $resource => $sources) {
                    foreach ($sources as $source) {
                        $source['targetType'] = $type;
                        $source['targetStack'] = $stackName;
                        $source['targetResource'] = $resource;
                        $dependants[] = $source;
                    }
                }
            }
        }
        return $dependants;
    }
}
