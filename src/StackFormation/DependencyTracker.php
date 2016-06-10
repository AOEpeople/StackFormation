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

    public function trackEnvUsage($envVar, $withDefault=false)
    {
        $type = $withDefault ? 'env_with_default' : 'env';
        if (!isset($this->envVars[$type])) {
            $this->envVars[$type] = [];
        }
        if (!isset($this->envVars[$type][$envVar])) {
            $this->envVars[$type][$envVar] = 0;
        }
        $this->envVars[$type][$envVar]++;
    }

    public function trackStackDependency($type, $stack, $resource)
    {
        if (!isset($this->stacks[$type])) {
            $this->stacks[$type] = [];
        }
        if (!isset($this->stacks[$type][$stack])) {
            $this->stacks[$type][$stack] = [];
        }
        if (!isset($this->stacks[$type][$stack][$resource])) {
            $this->stacks[$type][$stack][$resource] = 0;
        }
        $this->stacks[$type][$stack][$resource]++;
    }

    public function getStackDependencies()
    {
        return $this->stacks;
    }

    public function getEnvDependencies()
    {
        return $this->envVars;
    }

    public function getStackDependenciesAsFlatList()
    {
        $rows = [];
        foreach ($this->stacks as $type => $typeData) {
            foreach ($typeData as $stack => $stackData) {
                foreach ($stackData as $resource => $count) {
                    $rows[] = [
                        $type,
                        $stack,
                        $resource,
                        $count
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
            foreach ($typeData as $envVar => $count) {
                $rows[] = [
                    $type,
                    $envVar,
                    $count
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
}
