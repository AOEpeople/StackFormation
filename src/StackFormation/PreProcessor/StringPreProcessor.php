<?php

namespace StackFormation\PreProcessor;

use StackFormation\Helper\Pipeline;

class StringPreProcessor {

    /**
     * @param string $content
     * @return string
     */
    public function process($content)
    {
        $stageClasses = [
            '\StackFormation\PreProcessor\Stage\String\StripComments',
        ];

        $pipeline = new Pipeline();
        foreach ($stageClasses as $stageClass) {
            $pipeline->addStage(new $stageClass($this));
        }

        return $pipeline->process($content);
    }
}
