<?php

class StaticCacheTest extends PHPUnit_Framework_TestCase {

    /**
     * @throws Exception
     * @test
     */
    public function storeInCache() {
        $result = \StackFormation\StaticCache::has('test');
        $this->assertFalse($result);

        \StackFormation\StaticCache::set('test', '42');
        $result = \StackFormation\StaticCache::has('test');
        $this->assertTrue($result);

        $result = \StackFormation\StaticCache::get('test');
        $this->assertEquals('42', $result);

        \StackFormation\StaticCache::delete('test');
        $result = \StackFormation\StaticCache::has('test');

        $this->assertFalse($result);
    }

    /**
     * @throws Exception
     * @test
     */
    public function storeInCacheWithCallback() {
        $result = \StackFormation\StaticCache::has('test');
        $this->assertFalse($result);

        $result = \StackFormation\StaticCache::get('test', function() {
            return '42';
        });
        $this->assertEquals('42', $result);

        $result = \StackFormation\StaticCache::get('test');
        $this->assertEquals('42', $result);

        \StackFormation\StaticCache::delete('test');
        $result = \StackFormation\StaticCache::has('test');

        $this->assertFalse($result);
    }
}