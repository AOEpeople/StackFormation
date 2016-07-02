<?php

namespace StackFormation\Tests;

use StackFormation\Helper\Validator;

class HelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $stackName
     * @throws \Exception
     * @test
     * @dataProvider validStackNameProvider
     */
    public function validStackName($stackName)
    {
        Validator::validateStackname($stackName);
    }

    /**
     * @return array
     */
    public function validStackNameProvider()
    {
        return [
            ['ecom-t-stack'],
            ['ecom123'],
            ['Ecom-t-stack'],
            [str_repeat('a', 128)],
        ];
    }

    /**
     * @param string $stackName
     * @throws \Exception
     * @test
     * @dataProvider invalidStackNameProvider
     */
    public function invalidStackName($stackName)
    {
        $this->setExpectedException('Exception');
        Validator::validateStackname($stackName);
    }

    /**
     * @return array
     */
    public function invalidStackNameProvider()
    {
        return [
            [new \stdClass()],
            [ []],
            [ null ],
            [ 42 ],
            ['ecom_t_stack'],
            [''],
            ['stackname with whitespace'],
            ['123ecom'],
            [str_repeat('a', 129)],
        ];
    }
}
