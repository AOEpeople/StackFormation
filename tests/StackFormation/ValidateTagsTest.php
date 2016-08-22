<?php

namespace StackFormation\Tests;

use StackFormation\Helper\Validator;

class ValidateTagsTest extends MockFacade
{
    /**
     * @param array $tag
     * @throws \Exception
     * @test
     * @dataProvider validTagsProvider
     */
    public function validTag(array $tag)
    {
        Validator::validateTags([$tag]);
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
        Validator::validateTags([$tag]);
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

    /**
     * @test
     */
    public function valueIsMissing()
    {
        $this->setExpectedException('Exception', 'Tag value is missing');
        Validator::validateTags([['Key' => 'foo']]);
    }

    /**
     * @test
     */
    public function keyIsMissing()
    {
        $this->setExpectedException('Exception', 'Tag key is missing');
        Validator::validateTags([['Value' => 'foo']]);
    }

    /**
     * @test
     */
    public function moreThanTenTags()
    {
        $this->setExpectedException('Exception', 'No more than 10 tags are allowed');
        $tags = [];
        for ($i=0; $i<11; $i++) {
            $tags[] = ['Key' => "Key$i", 'Value' => "Value$i"];
        }
        Validator::validateTags($tags);
    }
}
