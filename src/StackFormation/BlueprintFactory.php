<?php

namespace StackFormation;

class BlueprintFactory {

    protected $config;

    public function __construct()
    {
        $this->config = new Config();
    }

    public function getBlueprint($blueprintName)
    {
        $blueprint = new Blueprint($blueprintName, $this->config->getBlueprintConfig($blueprint));
        return $blueprint;
    }

}