<?php

namespace StackFormation\Helper;


class StaticCache
{

    protected static $cache;

    /**
     * @return Cache
     */
    public function getCache()
    {
        if (is_null(self::$cache)) {
            self::$cache = new Cache();
        }
        return self::$cache;
    }

    /**
     * Get (and generate)
     *
     * @param $key
     * @param callable|null $callback
     * @param bool $fresh
     * @return mixed
     * @throws \Exception
     */
    public static function get($key, callable $callback = null, $fresh = false)
    {
        return self::getCache()->get($key, $callback, $fresh);
    }

    /**
     * Has
     *
     * @param $key
     * @return bool
     */
    public function has($key)
    {
        return self::getCache()->has($key);
    }

    /**
     * Set
     *
     * @param $key
     * @param $value
     */
    public function set($key, $value)
    {
        return self::getCache()->set($key, $value);
    }

    /**
     * Delete
     *
     * @param $key
     */
    public function delete($key)
    {
        return self::getCache()->delete($key);
    }


}
