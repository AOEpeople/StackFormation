<?php

namespace StackFormation\PreProcessor\Stage;

use StackFormation\Template;

abstract class AbstractTreePreProcessorStage
{
    protected $treePreProcessor; // reference to parent
    protected $template;

    /**
     * @param \StackFormation\PreProcessor\TreePreProcessor $treePreProcessor
     * @param Template $template
     */
    public function __construct(\StackFormation\PreProcessor\TreePreProcessor $treePreProcessor, Template $template) {
        $this->treePreProcessor = $treePreProcessor;
        $this->template = $template;
    }

    /**
     * @throws \StackFormation\Exception\TreePreProcessorException
     */
    public function __invoke()
    {
        try {
            $tree = $this->template->getTree();
            $this->invoke($tree);
            $this->template->setTree($tree);
        } catch (\Exception $e) {
            throw new \StackFormation\Exception\TreePreProcessorException($this->template, $e);
        }
    }

    /**
     * @param $tree
     */
    abstract function invoke(array &$tree);
}
