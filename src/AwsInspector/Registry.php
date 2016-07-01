<?php

namespace AwsInspector;

class Registry {

    /**
     * @var array
     */
    protected static $items = [];

    /**
     * @param string $key
     * @param mixed $object
     */
    public static function set($key, $object) {
        self::$items[$key] = $object;
    }

    /**
     * @param string $key
     * @return bool|mixed
     */
    public static function get($key) {
        if (!isset(self::$items[$key])) {
            return false;
        }

        return self::$items[$key];
    }
}
