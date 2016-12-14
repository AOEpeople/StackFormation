<?php

namespace StackFormation\Tests\PreProcessor\Stage\String;

class StripCommentsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @dataProvider stripCommentsDataProvider
     */
    public function stripComments($string, $expected)
    {
        $stringPreProcessor = $this->getMock('\StackFormation\PreProcessor\StringPreProcessor', [], [], '', false);
        $transformer = new \StackFormation\PreProcessor\Stage\String\StripComments($stringPreProcessor);
        $output = $transformer->invoke($string);
        $this->assertEquals($expected, $output);
    }

    /**
     * @return array
     */
    public function stripCommentsDataProvider()
    {
        return [
            ['This is a string /** comment blabla */', 'This is a string '],
            ['This is a string /** comment blabla */ with a comment between', 'This is a string  with a comment between'],
            ['arn:aws:s3:::my-bucket/*', 'arn:aws:s3:::my-bucket/*'],
        ];
    }
}
