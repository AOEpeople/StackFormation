<?php

namespace AwsInspector\Tests;

class RegistryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function getReturnsExpectedString()
    {
        $expectedValue = 'My String';
        \AwsInspector\Registry::set('key_phpunit_getReturnsExpectedString', $expectedValue);
        $value = \AwsInspector\Registry::get('key_phpunit_getReturnsExpectedString');
        $this->assertSame($expectedValue, $value);
    }

    /**
     * @test
     */
    public function getReturnsExpectedObject()
    {
        $object = new \stdClass();
        $object->value = 'My String';
        \AwsInspector\Registry::set('key_phpunit_getReturnsExpectedObject', $object);
        $value = \AwsInspector\Registry::get('key_phpunit_getReturnsExpectedObject');
        $this->assertTrue($value instanceof \stdClass);
        $this->assertSame($object->value, $value->value);
    }

    /**
     * @test
     */
    public function getReturnsFalseIfExpectedKeyIsNotAvailable()
    {
        $value = \AwsInspector\Registry::get('key_phpunit_getReturnsFalseIfExpectedKeyIsNotAvailable');
        $this->assertFalse($value);
    }
}
