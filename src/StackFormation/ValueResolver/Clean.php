<?php

namespace StackFormation\ValueResolver;


class Clean extends AbstractValueResolverStage
{

    public function invoke($string)
    {
        $string = preg_replace_callback(
            '/\{clean:([^:\}\{]+?)\}/',
            function ($matches) {
                return preg_replace('/[^-a-zA-Z0-9]/', '', $matches[1]);
            },
            $string
        );
        return $string;
    }

}
