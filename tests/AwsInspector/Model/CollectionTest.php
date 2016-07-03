<?php

namespace AwsInspector\Tests\Model;

class CollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function getFirstReturnsFirstObjectOfCollection()
    {
        $collection = new \AwsInspector\Model\Collection();

        $a = new \stdClass();
        $a->title = 'StackFormation';
        $collection->attach($a);

        $b = new \stdClass();
        $b->title = 'WhateverElse';
        $collection->attach($b);

        $collection->next();

        $this->assertSame($a, $collection->getFirst());
        $this->assertSame('StackFormation', $collection->getFirst()->title);
    }
}
