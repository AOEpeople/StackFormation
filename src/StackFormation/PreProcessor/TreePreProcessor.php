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
            '\StackFormation\PreProcessor\Stage\Tree\InjectFilecontent',

            # TODO, check also if we still need that
            #'\StackFormation\PreProcessor\Stage\Tree\ParseRefInDoubleQuotedStrings',
            #'\StackFormation\PreProcessor\Stage\Tree\Base64encodedJson',
            #'\StackFormation\PreProcessor\Stage\Tree\Split',
            #'\StackFormation\PreProcessor\Stage\Tree\ReplaceFnGetAttr',
            #'\StackFormation\PreProcessor\Stage\Tree\ReplaceRef',
            #'\StackFormation\PreProcessor\Stage\Tree\ReplaceMarkers',
        ];

        $pipeline = new Pipeline();
        foreach ($stageClasses as $stageClass) {
            $pipeline->addStage(new $stageClass($this, $template));
        }

        $pipeline->process('');
    }

    /**
     * @param string $expression
     * @param array $tree
     * @param callable $callback
     * @param bool $expressionUsedOnKey
     */
    public function searchTreeByExpression($expression, array &$tree, callable $callback, $expressionUsedOnKey = false)
    {
        #print_r($tree);die();

        foreach ($tree as $key => &$leaf) {
            if (is_array($leaf)) {
                $this->searchTreeByExpression($expression, $leaf, $callback, $expressionUsedOnKey);
                continue;
            }

            $subject = ($expressionUsedOnKey === true ? $key : $leaf);
            preg_replace_callback($expression, function(array $matches) use ($callback, &$tree, $key, $leaf) {
                $callback($tree, $key, $leaf, $matches);
            }, $subject);
        }
    }
}
