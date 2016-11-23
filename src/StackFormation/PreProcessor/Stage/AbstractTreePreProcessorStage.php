<?php

namespace StackFormation\PreProcessor\Stage;

use StackFormation\Template;

abstract class AbstractTreePreProcessorStage
{
    protected $treePreProcessor; // reference to parent

    /**
     * @param \StackFormation\PreProcessor\TreePreProcessor $treePreProcessor
     */
    public function __construct(\StackFormation\PreProcessor\TreePreProcessor $treePreProcessor) {
        $this->treePreProcessor = $treePreProcessor;
    }

    /**
     * @param Template $template
     * @throws \StackFormation\Exception\TreePreProcessorException
     */
    public function __invoke(Template $template)
    {
        try {
            return $this->invoke($template);
        } catch (\Exception $e) {
            throw new \StackFormation\Exception\TreePreProcessorException($template, $e);
        }
    }

    /**
     * @param Template $template
     */
    abstract function invoke(Template $template);
}
