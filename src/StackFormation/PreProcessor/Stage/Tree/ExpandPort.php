<?php

namespace StackFormation\PreProcessor\Stage\Tree;

use StackFormation\PreProcessor\Stage\AbstractTreePreProcessorStage;

class ExpandPort extends AbstractTreePreProcessorStage
{
    /**
     * @param array $tree
     */
    public function invoke(array &$tree)
    {
        $this->treePreProcessor->searchTreeByExpression('/^Port$/', $tree, function (&$tree, $key, $value, $matches) {
            unset($tree[$key]);
            $tree['FromPort'] = $value;
            $tree['ToPort'] = $value;
        }, true);
    }
}
