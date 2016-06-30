<?php

namespace StackFormation;

/**
 * Class StaticCache
 *
 * Quick'n'dirty static cache with convenience method
 *
 * Example:
 * StaticCache::get('message', function() { return 'Hello World'; });
 *
 * Will return the message from cache if present and will generate it
 * by executing the callback and store it in the cache before returning the value.
 *
 * @author Fabrizio Branca
 */
class StaticCache
{

    protected static $cache = [];

    /**
     * Get (and generate)
     *
     * @param $key
     * @param callable|null $callback
     * @return mixed
     * @throws \Exception
     */
    public static function get($key, callable $callback = null, $fresh = false)
    {
        if ($fresh || !self::has($key)) {
            if (!is_null($callback)) {
                self::set($key, $callback());
            } else {
                throw new \Exception(sprintf("Cache key '%s' not found.", $key));
            }
        }
        return self::$cache[$key];
    }

    /**
     * Has
     *
     * @param $key
     * @return bool
     */
    public static function has($key)
    {
        return isset(self::$cache[$key]);
    }

    /**
     * Set
     *
     * @param $key
     * @param $value
     */
    public static function set($key, $value)
    {
        self::$cache[$key] = $value;
    }

    /**
     * Delete
     *
     * @param $key
     */
    public static function delete($key)
    {
        unset(self::$cache[$key]);
    }
}
