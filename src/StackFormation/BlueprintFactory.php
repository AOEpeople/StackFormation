<?php

namespace StackFormation;

class BlueprintFactory {

    protected $config;

    public function __construct(\Aws\CloudFormation\CloudFormationClient $cfnClient, Config $config, PlaceholderResolver $resolver)
    {
        $this->cfnClient = $cfnClient;
        $this->config = $config;
        $this->resolver = $resolver;
    }

    public function getBlueprint($blueprintName)
    {
        if (!$this->blueprintExists($blueprintName)) {
            throw new \Exception("Blueprint '$blueprintName' does not exist'");
        }
        $blueprint = new Blueprint(
            $blueprintName,
            $this->config,
            $this->resolver,
            $this->cfnClient
        );
        return $blueprint;
    }

    public function blueprintExists($blueprint)
    {
        return $this->config->blueprintExists($blueprint);
    }

    public function getBlueprintLabels($filter=null)
    {
        $labels = [];
        foreach ($this->config->getBlueprintNames() as $blueprintName) {
            try {
                $effectiveStackName = $this->getBlueprint($blueprintName)->getStackName();
            } catch (\Exception $e) {
                $effectiveStackName = '[Missing env var] Error: ' . $e->getMessage();
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