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

    /**
     * @param string $string
     * @return bool
     */
    public static function isJson($string) {
        $string = trim($string);

        // TODO just a workaround to check if the string is a valid JSON
        // we could not check that with json_decode because, it could be that the JSON file has some comments inside
        // and the StringPreProcessor/StripComments is invoked after that check!!
        return preg_match('/\A\{(.*)/', $string);
    }
}
