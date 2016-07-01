<?php

namespace StackFormation\Tests;

class HelperTest extends \PHPUnit_Framework_TestCase {

    /**
     * @test
     * @dataProvider validStackNameProvider
     */
    public function validStackName($stackName) {
        \StackFormation\Helper::validateStackname($stackName);
    }

    public function validStackNameProvider() {
        return [
            ['ecom-t-stack'],
            ['ecom123'],
            ['Ecom-t-stack'],
            [str_repeat('a', 128)],
        ];
    }

    /**
     * @test
     * @dataProvider invalidStackNameProvider
     */
    public function invalidStackName($stackName) {
        $this->setExpectedException('Exception');
        \StackFormation\Helper::validateStackname($stackName);
    }

    public function invalidStackNameProvider() {
        return [
            ['ecom_t_stack'],
            [''],
            ['stackname with whitespace'],
            ['123ecom'],
            [str_repeat('a', 129)],
        ];
    }

}