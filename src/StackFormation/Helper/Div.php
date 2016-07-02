<?php

namespace StackFormation\Helper;

class Div
{

    public static function flatten(array $array, $keyKey = 'Key', $valueKey = 'Value')
    {
        $tmp = [];
        foreach ($array as $item) {
            $tmp[$item[$keyKey]] = $item[$valueKey];
        }
        return $tmp;
    }

    public static function assocArrayToString(array $array, $itemSeparator = '; ', $keyValueSeparator = '=')
    {
        $tmp = [];
        foreach ($array as $key => $value) {
            $tmp[] = "$key$keyValueSeparator$value";
        }
        return implode($itemSeparator, $tmp);
    }

    /**
     * @param string $program
     * @return bool
     * @see n98-magerun/src/N98/Util/OperatingSystem.php
     */
    public static function isProgramInstalled($program)
    {
        $out = null;
        $return = null;
        @exec('which ' . $program, $out, $return);
        return $return === 0;
    }
}
