<?php

namespace StackFormation\ValueResolver;

class EnvironmentVariableWithFallback extends AbstractValueResolverStage
{

    public function invoke($string)
    {
        $string = preg_replace_callback(
            '/\{env:([^:\}\{]+?):([^:\}\{]+?)\}/',
            function ($matches) {
                $value = getenv($matches[1]);
                $value = $value ? $value : $matches[2];
                $this->dependencyTracker->trackEnvUsage($matches[1], true, $value, $this->sourceBlueprint, $this->sourceType, $this->sourceKey);
                return $value;
            },
            $string
        );
        return $string;
    }

}
