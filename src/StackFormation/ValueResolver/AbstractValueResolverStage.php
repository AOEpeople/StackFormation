<?php

namespace StackFormation\ValueResolver;

use League\Pipeline\StageInterface;
use StackFormation\Blueprint;
use StackFormation\Config;
use StackFormation\DependencyTracker;
use StackFormation\Profile\Manager;
use StackFormation\StackFactory;
use StackFormation\ValueResolver;

abstract class AbstractValueResolverStage implements StageInterface
{

    protected $valueResolver; // reference to parent
    protected $profileManager;
    protected $config;
    protected $dependencyTracker;
    protected $sourceBlueprint;
    protected $sourceType;
    protected $sourceKey;

    public function __construct(
        ValueResolver $valueResolver,
        Manager $profileManager,
        Config $config,
        DependencyTracker $dependencyTracker,
        Blueprint $sourceBlueprint=null,
        $sourceType=null,
        $sourceKey=null
    ) {
        $this->valueResolver = $valueResolver;
        $this->profileManager = $profileManager;
        $this->dependencyTracker = $dependencyTracker;
        $this->config = $config;
        $this->sourceBlueprint = $sourceBlueprint;
        $this->sourceType = $sourceType;
        $this->sourceKey = $sourceKey;
    }

    public function __invoke($string)
    {
        return $this->invoke($string);
    }

    abstract function invoke($string);

    /**
     * Convenience method
     *
     * @return StackFactory
     */
    protected function getStackFactory()
    {
        return $this->valueResolver->getStackFactory($this->sourceBlueprint);
    }

    /**
     * Craft exception message appendix
     *
     * @return string
     */
    protected function getExceptionMessageAppendix()
    {
        $tmp = [];
        if ($this->sourceBlueprint) { $tmp[] = 'Blueprint: ' . $this->sourceBlueprint->getName(); }
        if ($this->sourceType) { $tmp[] = 'Type:' . $this->sourceType; }
        if ($this->sourceKey) { $tmp[] = 'Key:' . $this->sourceKey; }
        if (count($tmp)) {
            return ' (' . implode(', ', $tmp) . ')';
        }
        return '';
    }

}
