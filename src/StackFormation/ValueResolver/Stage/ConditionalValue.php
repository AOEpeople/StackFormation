<?php

namespace StackFormation\ValueResolver\Stage;

use StackFormation\Blueprint;

class ConditionalValue extends AbstractValueResolverStage
{

    public function invoke($values)
    {
        if (!is_array($values)) {
            return $values;
        }
        foreach ($values as $condition => $value) {
            if ($this->isTrue($condition, $this->sourceBlueprint)) {
                return $value;
            }
        }
        return '';
    }

    /**
     * Evaluate is key 'is true'
     *
     * @param $condition
     * @param Blueprint|null $sourceBlueprint
     * @return bool
     * @throws \Exception
     */
    public function isTrue($condition, Blueprint $sourceBlueprint=null)
    {
        // resolve placeholders
        $condition = $this->valueResolver->resolvePlaceholders($condition, $sourceBlueprint, 'conditional_value', $condition);

        if ($condition == 'default') {
            return true;
        }
        if (strpos($condition, '==') !== false) {
            list($left, $right) = explode('==', $condition, 2);
            $left = trim($left);
            $right = trim($right);
            return ($left == $right);
        } elseif (strpos($condition, '!=') !== false) {
            list($left, $right) = explode('!=', $condition, 2);
            $left = trim($left);
            $right = trim($right);
            return ($left != $right);
        } elseif (strpos($condition, '~=') !== false) {
            list($subject, $pattern) = explode('~=', $condition, 2);
            $subject = trim($subject);
            $pattern = trim($pattern);
            return preg_match($pattern, $subject);
        }
        throw new \Exception('Invalid condition: ' . $condition);
    }

}
