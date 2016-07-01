<?php

namespace StackFormation\Tests;

class ValidateTagsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param array $tag
     * @throws \Exception
     * @test
     * @dataProvider validTagsProvider
     */
    public function validTag(array $tag)
    {
        \StackFormation\Helper::validateTags([$tag]);
    }

    /**
     * @return array
     */
    public function validTagsProvider()
    {
        return [
            [['Key' => 'Name', 'Value' => 'Bar']],
            [['Key' => str_repeat('A', 127), 'Value' => 'Bar']],
            [['Key' => 'Name', 'Value' => str_repeat('A', 255)]],
            [['Key' => 'Name', 'Value' => base64_encode('&*#,!')]],
        ];
    }

    /**
     * @param array $tag
     * @throws \Exception
     * @test
     * @dataProvider invalidTagsProvider
     */
    public function invalidTag(array $tag)
    {
        $this->setExpectedException('Exception');
        \StackFormation\Helper::validateTags([$tag]);
    }

    /**
     * @return array
     */
    public function invalidTagsProvider()
    {
        return [
            [['Key' => 'aws:Name', 'Value' => 'Bar']],
            [['Name' => 'Foo', 'Value' => 'Bar']],
            [['Key' => 'Na,me', 'Value' => 'Bar']],
            [['Key' => 'Name', 'Value' => 'Ba,r']],
            [['Key' => str_repeat('A', 128), 'Value' => 'Bar']],
            [['Key' => 'Name', 'Value' => str_repeat('A', 256)]],
        ];
    }
}
