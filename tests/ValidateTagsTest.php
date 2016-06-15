<?php

class ValidateTagsTest extends PHPUnit_Framework_TestCase {

    /**
     * @test
     * @dataProvider validTagsProvider
     */
    public function validTag(array $tag) {
        StackFormation\Helper::validateTags([$tag]);
    }

    public function validTagsProvider() {
        return [
            [['Key' => 'Name', 'Value' => 'Bar']],
            [['Key' => str_repeat('A', 127), 'Value' => 'Bar']],
            [['Key' => 'Name', 'Value' => str_repeat('A', 255)]],
            [['Key' => 'Name', 'Value' => base64_encode('&*#,!')]],
        ];
    }

    /**
     * @test
     * @dataProvider invalidTagsProvider
     */
    public function invalidTag(array $tag) {
        $this->setExpectedException('Exception');
        StackFormation\Helper::validateTags([$tag]);
    }

    public function invalidTagsProvider() {
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