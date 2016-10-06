<?php

namespace StackFormation;

use StackFormation\Exception\BlueprintNotFoundException;
use StackFormation\Exception\MissingEnvVarException;
use StackFormation\Helper\Finder;
use StackFormation\ValueResolver\ValueResolver;

class BlueprintFactory {

    protected $config;
    protected $valueResolver;

    public function __construct(Config $config=null, ValueResolver $valueResolver=null)
    {
        $this->config = $config ?: new Config();
        $this->valueResolver = $valueResolver ?: new ValueResolver(null, null, $this->config, null);
    }

    public function getBlueprint($blueprintName)
    {
        if (!$this->blueprintExists($blueprintName)) {
            throw new BlueprintNotFoundException($blueprintName);
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
        $blueprintName = $stack->getBlueprintName();
        return $this->getBlueprint($blueprintName);
    }

    public function blueprintExists($blueprint)
    {
        return $this->config->blueprintExists($blueprint);
    }

    public function findByStackname($stackname)
    {
        foreach ($this->config->getBlueprintNames() as $blueprintName) {
            if (strpos($blueprintName, '{env:') !== false) {
                $regex = preg_replace('/\{env:([^:\}\{]+?)\}/', '(?P<$1>\w+)', $blueprintName);
                $matches = [];
                if (preg_match('/' . $regex . '/', $stackname, $matches)) {
                    foreach ($matches as $key => $value) {
                        if (is_int($key)) {
                            unset($matches[$key]);
                        }
                    }
                    return [
                        'blueprint' => $blueprintName,
                        'envvars' => $matches
                    ];
                }
            }
        }
        return false;
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
            } catch (\StackFormation\Exception\InvalidStackNameException $e) {
                $effectiveStackName = '<fg=red>[Invalid stack name "' . $e->getStackName() . '"]</>';
            } catch (\StackFormation\Exception\ValueResolverException $e) {
                $previousException = $e->getPrevious();
                if ($previousException instanceof MissingEnvVarException) {
                    $effectiveStackName = '<fg=red>[Missing env var "' . $previousException->getEnvVar() . '"]</>';
                } else {
                    throw $e;
                }
            }
            $label = $blueprintName;

            if (!is_null($filter) && !Finder::matchWildcard($filter, $label)) {
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
