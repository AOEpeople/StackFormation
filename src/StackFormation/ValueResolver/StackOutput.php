<?php

namespace StackFormation\ValueResolver;

use StackFormation\Helper;

class StackOutput extends AbstractValueResolverStage
{

    public function invoke($string)
    {
        $string = preg_replace_callback(
            '/\{output:([^:\}\{]+?):([^:\}\{]+?)\}/',
            function ($matches) {
                $this->valueResolver->getDependencyTracker()->trackStackDependency('output', $matches[1], $matches[2], $this->sourceBlueprint, $this->sourceType, $this->sourceKey);
                return $this->getStackFactory()->getStackOutput($matches[1], $matches[2]);
            },
            $string
        );
        return $string;
    }

}
