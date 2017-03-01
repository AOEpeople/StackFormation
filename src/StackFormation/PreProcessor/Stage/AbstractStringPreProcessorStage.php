<?php

namespace StackFormation\PreProcessor\Stage;

abstract class AbstractStringPreProcessorStage
{
    protected $stringPreProcessor; // reference to parent

    /**
     * @param \StackFormation\PreProcessor\StringPreProcessor $stringPreProcessor
     */
    public function __construct(\StackFormation\PreProcessor\StringPreProcessor $stringPreProcessor) {
        $this->stringPreProcessor = $stringPreProcessor;
    }

    /**
     * @param string $content
     * @throws \StackFormation\Exception\StringPreProcessorException
     */
    public function __invoke($content)
    {
        try {
            return $this->invoke($content);
        } catch (\Exception $e) {
            throw new \StackFormation\Exception\StringPreProcessorException($content, $e);
        }
    }

    /**
     * @param string $content
     */
    abstract function invoke($content);
}
