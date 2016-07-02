<?php

namespace StackFormation\ValueResolver;


class Variable extends AbstractValueResolverStage
{

    public function invoke($string)
    {
        $string = preg_replace_callback(
            '/\{var:([^:\}\{]+?)\}/',
            function ($matches) {
                $vars = $this->valueResolver->getConfig()->getGlobalVars();
                if ($this->sourceBlueprint) {
                    $vars = array_merge($vars, $this->sourceBlueprint->getVars());
                }
                if (!isset($vars[$matches[1]])) {
                    throw new \Exception("Variable '{$matches[1]}' not found");
                }
                $value = $vars[$matches[1]];
                if (is_array($value)) {
                    $value = $this->valueResolver->resolvePlaceholders($value, $this->sourceBlueprint);
                }
                if ($value == $matches[0]) {
                    throw new \Exception('Direct circular reference detected');
                }
                return $value;
            },
            $string
        );
        return $string;
    }

}
