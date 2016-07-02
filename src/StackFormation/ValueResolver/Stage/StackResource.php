<?php

namespace StackFormation\ValueResolver\Stage;

use StackFormation\Helper;

class StackResource extends AbstractValueResolverStage
{

    public function invoke($string)
    {
        $string = preg_replace_callback(
            '/\{resource:([^:\}\{]+?):([^:\}\{]+?)\}/',
            function ($matches) {
                $this->valueResolver->getDependencyTracker()->trackStackDependency('resource', $matches[1], $matches[2], $this->sourceBlueprint, $this->sourceType, $this->sourceKey);
                return $this->getStackFactory()->getStackResource($matches[1], $matches[2]);
            },
            $string
        );
        return $string;
    }

}
