<?php

namespace StackFormation;

use StackFormation\Exception\BlueprintNotFoundException;
use StackFormation\Exception\MissingEnvVarException;
use StackFormation\Exception\TagNotFoundException;

class BlueprintFactory {

    protected $config;
    protected $valueResolver;

    public function __construct(Config $config=null, ValueResolver $valueResolver=null)
    {
        $this->config = $config ? $config : new Config();
        if (is_null($valueResolver)) {
            $valueResolver = new ValueResolver(null, null, $this->config, null);
        }
        $this->valueResolver = $valueResolver;
    }

    public function getBlueprint($blueprintName)
    {
        if (!$this->blueprintExists($blueprintName)) {
            throw new BlueprintNotFoundException("Blueprint '$blueprintName' does not exist'");
        }
        $blueprint = new Blueprint(
            $blueprintName,
            $this->config->getBlueprintConfig($blueprintName),
            $this->valueResolver
        );
        return $blueprint;
    }

    public function getBlueprintByStack(Stack $stack)
    {
        try {
            $blueprintName = $stack->getBlueprintName();
            return $this->getBlueprint($blueprintName);
        } catch (TagNotFoundException $e) {
            // let's try if there's a blueprint with the same name
            return $this->getBlueprint($stack->getName());
        }
    }

    public function blueprintExists($blueprint)
    {
        return $this->config->blueprintExists($blueprint);
    }

    /**
     * @return Blueprint[]
     * @throws \Exception
     */
    public function getAllBlueprints()
    {
        $blueprints = [];
        foreach ($this->config->getBlueprintNames() as $blueprintName) {
            $blueprints[$blueprintName] = $this->getBlueprint($blueprintName);
        }
        return $blueprints;
    }

    public function getBlueprintLabels($filter=null)
    {
        $labels = [];
        foreach ($this->config->getBlueprintNames() as $blueprintName) {
            try {
                $effectiveStackName = $this->getBlueprint($blueprintName)->getStackName();
            } catch (MissingEnvVarException $e) {
                $effectiveStackName = '<fg=red>[Missing env var "'.$e->getEnvVar().'"]</>';
            }
            $label = $blueprintName;

            if (!is_null($filter) && !Helper::matchWildcard($filter, $label)) {
                continue;
            }

            if ($effectiveStackName != $blueprintName) {
                $label .= " <fg=yellow>(Effective: $effectiveStackName)</>";
            }
            $labels[] = $label;
        }
        return $labels;
    }

}