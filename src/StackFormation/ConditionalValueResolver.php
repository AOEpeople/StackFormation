<?php

namespace StackFormation;

class ConditionalValueResolver {

    protected $placeholderResolver;

    public function __construct(PlaceholderResolver $placeholderResolver)
    {
        $this->placeholderResolver = $placeholderResolver;
    }

    /**
     * Resolve conditional value
     *
     * @param array $values
     * @param Blueprint|null $sourceBlueprint
     * @return string
     * @throws \Exception
     */
    public function resolveConditionalValue(array $values, Blueprint $sourceBlueprint=null)
    {
        foreach ($values as $condition => $value) {
            if ($this->isTrue($condition, $sourceBlueprint)) {
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
        $condition = $this->placeholderResolver->resolvePlaceholders($condition, $sourceBlueprint, 'conditional_value', $condition);

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
        } else {
            throw new \Exception('Invalid condition');
        }
    }

}