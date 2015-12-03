<?php

namespace StackFormation;

class Helper
{

    public function matchWildcard($wildcard_pattern, $haystack)
    {
        $regex = str_replace(
            ["\*", "\?"],
            ['.*', '.'],
            preg_quote($wildcard_pattern)
        );

        return preg_match('/^' . $regex . '$/is', $haystack);
    }

    public function find($wildcardPattern, $choices)
    {
        $found = [];
        foreach ($choices as $choice) {
            if ($this->matchWildcard($wildcardPattern, $choice)) {
                $found[] = $choice;
            }
        }

        return $found;
    }
}
