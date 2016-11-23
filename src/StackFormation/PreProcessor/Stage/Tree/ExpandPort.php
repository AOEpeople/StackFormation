<?php

namespace StackFormation\PreProcessor\Stage\Tree;

use StackFormation\PreProcessor\Stage\AbstractTreePreProcessorStage;
use StackFormation\Template;

class ExpandPort extends AbstractTreePreProcessorStage
{
    /**
     * @param Template $template
     * @return Template $template
     */
    public function invoke(Template $template)
    {
        $tree = $template->getTree();
        $this->treePreProcessor->searchTreeByExpression('/^Port$/', $tree, function (&$tree, $key, $value) {
            unset($tree[$key]);
            $tree['FromPort'] = $value;
            $tree['ToPort'] = $value;
        }, 'key');
        $template->setTree($tree);

        return $template;
    }
}
