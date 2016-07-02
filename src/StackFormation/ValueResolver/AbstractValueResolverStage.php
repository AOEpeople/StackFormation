<?php

namespace StackFormation\ValueResolver;

use League\Pipeline\StageInterface;
use StackFormation\Blueprint;
use StackFormation\Exception\ValueResolverException;
use StackFormation\StackFactory;
use StackFormation\ValueResolver;

abstract class AbstractValueResolverStage implements StageInterface
{

    protected $valueResolver; // reference to parent
    protected $sourceBlueprint;
    protected $sourceType;
    protected $sourceKey;

    public function __construct(
        ValueResolver $valueResolver,
        Blueprint $sourceBlueprint=null,
        $sourceType=null,
        $sourceKey=null
    ) {
        $this->valueResolver = $valueResolver;
        $this->sourceBlueprint = $sourceBlueprint;
        $this->sourceType = $sourceType;
        $this->sourceKey = $sourceKey;
    }

    public function __invoke($string)
    {
        try {
            return $this->invoke($string);
        } catch (\Exception $e) {
            throw new ValueResolverException($this->sourceBlueprint, $this->sourceType, $this->sourceKey, $e);
        }
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

}
