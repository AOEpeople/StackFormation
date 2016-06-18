<?php

namespace AwsInspector;


class Registry {

    protected static $items = [];

    public static function set($key, $object) {
        self::$items[$key] = $object;
    }

    public static function get($key) {
        if (!isset(self::$items[$key])) {
            return false;
        }
        return self::$items[$key];
    }

}