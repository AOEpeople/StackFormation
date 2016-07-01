<?php

namespace StackFormation\Tests;

class CacheTest extends \PHPUnit_Framework_TestCase
{
    protected $cache;

    public function setUp()
    {
        parent::setUp();
        $this->cache = new \StackFormation\Helper\Cache();
    }

    /**
     * @throws Exception
     * @test
     */
    public function storeInCache()
    {
        $result = $this->cache->has('test');
        $this->assertFalse($result);

        $this->cache->set('test', '42');
        $result = $this->cache->has('test');
        $this->assertTrue($result);

        $result = $this->cache->get('test');
        $this->assertEquals('42', $result);

        $this->cache->delete('test');
        $result = $this->cache->has('test');

        $this->assertFalse($result);
    }

    /**
     * @throws Exception
     * @test
     */
    public function storeInCacheWithCallback()
    {
        $result = $this->cache->has('test');
        $this->assertFalse($result);

        $result = $this->cache->get('test', function()
        {
            return '42';
        });
        $this->assertEquals('42', $result);

        $result = $this->cache->get('test');
        $this->assertEquals('42', $result);

        $this->cache->delete('test');
        $result = $this->cache->has('test');

        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function keyNotFound()
    {
        $this->setExpectedException('Exception', "Cache key 'doesnotexist' not found.");
        $this->cache->get('doesnotexist');
    }
}
