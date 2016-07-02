<?php

namespace StackFormation\ValueResolver\Stage;

abstract class AbstractValueResolverStage
{

    protected $valueResolver; // reference to parent
    protected $sourceBlueprint;
    protected $sourceType;
    protected $sourceKey;

    public function __construct(
        \StackFormation\ValueResolver\ValueResolver $valueResolver,
        \StackFormation\Blueprint $sourceBlueprint=null,
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
            throw new \StackFormation\Exception\ValueResolverException($string, $this->sourceBlueprint, $this->sourceType, $this->sourceKey, $e);
        }
    }

    abstract function invoke($string);

    /**
     * Convenience method
     *
     * @return \StackFormation\StackFactory
     */
    protected function getStackFactory()
    {
        return $this->valueResolver->getStackFactory($this->sourceBlueprint);
    }

}