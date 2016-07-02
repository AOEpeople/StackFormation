<?php

namespace StackFormation\ValueResolver;

use StackFormation\Exception\MissingEnvVarException;

class EnvironmentVariable extends AbstractValueResolverStage
{

    public function invoke($string)
    {
        $string = preg_replace_callback(
            '/\{env:([^:\}\{]+?)\}/',
            function ($matches) {
                $value = getenv($matches[1]);
                if (!$value) {
                    throw new MissingEnvVarException($matches[1]);
                }
                $this->valueResolver->getDependencyTracker()->trackEnvUsage($matches[1], false, $value, $this->sourceBlueprint, $this->sourceType, $this->sourceKey);
                return getenv($matches[1]);
            },
            $string
        );
        return $string;
    }

}
