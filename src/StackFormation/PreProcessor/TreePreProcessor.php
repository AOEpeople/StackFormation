<?php

namespace StackFormation\PreProcessor;

use StackFormation\Template;
use StackFormation\Helper\Pipeline;

class TreePreProcessor {

    /**
     * @param Template $template
     * @return Template $template
     */
    public function process(Template $template)
    {
        $stageClasses = [
            '\StackFormation\PreProcessor\Stage\Tree\ExpandPort',

            # TODO, check also if we still need that
            #'\StackFormation\PreProcessor\Stage\Tree\ParseRefInDoubleQuotedStrings',
            #'\StackFormation\PreProcessor\Stage\Tree\InjectFilecontent',
            #'\StackFormation\PreProcessor\Stage\Tree\Base64encodedJson',
            #'\StackFormation\PreProcessor\Stage\Tree\Split',
            #'\StackFormation\PreProcessor\Stage\Tree\ReplaceFnGetAttr',
            #'\StackFormation\PreProcessor\Stage\Tree\ReplaceRef',
            #'\StackFormation\PreProcessor\Stage\Tree\ReplaceMarkers',
        ];

        $pipeline = new Pipeline();
        foreach ($stageClasses as $stageClass) {
            $pipeline->addStage(new $stageClass($this));
        }

        return $pipeline->process($template);
    }

    /**
     * @param string $expression
     * @param array $tree
     * @param callable $callback
     * @param string $mode
     */
    public function searchTreeByExpression($expression, array &$tree, callable $callback, $mode = '')
    {
        foreach ($tree as $key => &$leaf) {
            if (is_array($leaf)) {
                $this->searchTreeByExpression($expression, $leaf, $callback, $mode);
                continue;
            }

            $subject = ($mode == 'key' ? $key : $leaf);
            if (preg_match($expression, $subject)) {
                $callback($tree, $key, $leaf);
            }
        }
    }
}
