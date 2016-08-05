<?php

namespace StackFormation\Helper;

/**
 * Class Cache
 *
 * Quick'n'dirty cache with convenience method
 *
 * Example:
 * $cache->get('message', function() { return 'Hello World'; });
 *
 * Will return the message from cache if present and will generate it
 * by executing the callback and store it in the cache before returning the value.
 *
 * @author Fabrizio Branca
 */
class Cache
{

    protected $cache = [];

    /**
     * Get (and generate)
     *
     * @param $key
     * @param callable|null $callback
     * @param bool $fresh
     * @return mixed
     * @throws \Exception
     */
    public function get($key, callable $callback = null, $fresh = false)
    {
        if ($fresh || !$this->has($key)) {
            if (!is_null($callback)) {
                $this->set($key, $callback());
            } else {
                throw new \Exception(sprintf("Cache key '%s' not found.", $key));
            }
        } /* else {
            echo "HIT!";
        } */
        return $this->cache[$key];
    }

    /**
     * Has
     *
     * @param $key
     * @return bool
     */
    public function has($key)
    {
        return isset($this->cache[$key]);
    }

    /**
     * Set
     *
     * @param $key
     * @param $value
     */
    public function set($key, $value)
    {
        $this->cache[$key] = $value;
    }

    /**
     * Delete
     *
     * @param $key
     */
    public function delete($key)
    {
        unset($this->cache[$key]);
    }
}
