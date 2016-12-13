<?php

namespace StackFormation\PreProcessor\Stage;

use StackFormation\PreProcessor\Rootline;

abstract class AbstractTreePreProcessorStage
{
    protected $treePreProcessor;
    protected $basePath;

    /**
     * @param \StackFormation\PreProcessor\TreePreProcessor $treePreProcessor
     * @param string $basePath
     */
    public function __construct(\StackFormation\PreProcessor\TreePreProcessor $treePreProcessor, $basePath) {
        $this->treePreProcessor = $treePreProcessor;
        $this->basePath = $basePath;
    }

    public function __invoke() {}

    /**
     * @param string $path
     * @param string $value
     * @param Rootline $rootLineReferences
     * @return mixed
     */
    abstract function invoke($path, $value, Rootline $rootLineReferences);
}
