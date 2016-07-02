<?php

namespace StackFormation\ValueResolver\Stage;

use StackFormation\Helper;

class StackParameter extends AbstractValueResolverStage
{

    public function invoke($string)
    {
        $string = preg_replace_callback(
            '/\{parameter:([^:\}\{]+?):([^:\}\{]+?)\}/',
            function ($matches) {
                $this->valueResolver->getDependencyTracker()->trackStackDependency('parameter', $matches[1], $matches[2], $this->sourceBlueprint, $this->sourceType, $this->sourceKey);
                return $this->getStackFactory()->getStackParameter($matches[1], $matches[2]);
            },
            $string
        );
        return $string;
    }

}
