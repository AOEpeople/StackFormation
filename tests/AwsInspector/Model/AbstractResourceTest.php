<?php

namespace AwsInspector\Tests\Model;

class AbstractTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function getApiDataReturnsExpectedArray()
    {
        $data = ['test' => 1, 'test2' => 2];
        $stub = $this->getMockForAbstractClass('\AwsInspector\Model\AbstractResource', [$data]);
        $this->assertSame($data, $stub->getApiData());
    }

    /**
     * @test
     */
    public function extractDataReturnsExpectedArray()
    {
        $data = [
            'foo' => [
                'bar' => ['baz' => 1],
                'bam' => ['baz' => 2],
                'boo' => ['bam' => 3]
            ]
        ];
        $stub = $this->getMockForAbstractClass('\AwsInspector\Model\AbstractResource', [$data]);

        $mapping = [
            'test' => 'foo.*.baz',
            'test2' => 'foo.*.bam'
        ];

        $expected = [
            'test' => [1, 2],
            'test2' => [3]
        ];

        $this->assertSame($expected, $stub->extractData($mapping));
    }

    /**
     * @test
     */
    public function magicCallReturnsExpectedFieldValue()
    {
        $data = ['Stack' => 'teststack'];
        $stub = $this->getMockForAbstractClass('\AwsInspector\Model\AbstractResource', [$data]);
        $this->assertSame('teststack', $stub->getStack());
    }

    /**
     * @test
     */
    public function magicCallReturnsNullIfExpectedFieldIsNotAvailable()
    {
        $data = ['stack' => 'teststack'];
        $stub = $this->getMockForAbstractClass('\AwsInspector\Model\AbstractResource', [$data]);
        $this->assertNull($stub->getStackName());
    }

    /**
     * @test
     */
    public function magicCallThrowsExceptionIfMethodDoesntStartWithGet()
    {
        $stub = $this->getMockForAbstractClass('\AwsInspector\Model\AbstractResource', [[]]);

        try {
            $stub->blabla();
        } catch (\Exception $e) {
            $this->assertContains('Invalid method \'blabla\' (class: ', $e->getMessage());
        }
    }

    /**
     * @test
     */
    public function getAssocTagsReturnsExpectedArray()
    {
        $data = [
            'Tags' => [
                ['Key' => 'testKey', 'Value' => 'testValue'],
                ['Key' => 'testKey2', 'Value' => 'testValue2']
            ]
        ];
        $stub = $this->getMockForAbstractClass('\AwsInspector\Model\AbstractResource', [$data]);

        $expected = [
            'testKey' => 'testValue',
            'testKey2' => 'testValue2'
        ];
        $this->assertSame($expected, $stub->getAssocTags());
    }

    /**
     * @test
     */
    public function getAssocTagsThrowsExceptionIfTagsAreNotSupported()
    {
        $stub = $this->getMockForAbstractClass('\AwsInspector\Model\AbstractResource', [[]], '', true, true, true, ['__call']);
        $this->setExpectedException('Exception', 'Tags are not supported');
        $stub->getAssocTags();
    }

    /**
     * @test
     */
    public function getTagReturnsExpectedTag()
    {
        $data = [
            'Tags' => [
                ['Key' => 'testKey', 'Value' => 'testValue'],
                ['Key' => 'testKey2', 'Value' => 'testValue2']
            ]
        ];
        $stub = $this->getMockForAbstractClass('\AwsInspector\Model\AbstractResource', [$data]);
        $this->assertSame('testValue2', $stub->getTag('testKey2'));
    }

    /**
     * @test
     */
    public function getTagReturnsNullIfExpectedTagDoesNotExist()
    {
        $data = ['Tags' => []];
        $stub = $this->getMockForAbstractClass('\AwsInspector\Model\AbstractResource', [$data]);
        $this->assertNull($stub->getTag('notAvailable'));
    }

    /**
     * @test
     */
    public function matchesTagsReturnsTrueIfTagsAndFilterMatches()
    {
        $data = [
            'Tags' => [
                ['Key' => 'testKey', 'Value' => 'testValue'],
                ['Key' => 'testKey2', 'Value' => 'testValue2']
            ]
        ];
        $stub = $this->getMockForAbstractClass('\AwsInspector\Model\AbstractResource', [$data]);
        $filter = [
            'testKey' => 'testValue',
            'testKey2' => 'testValue2'
        ];

        $this->assertTrue($stub->matchesTags($filter));
    }

    /**
     * @test
     */
    public function matchesTagsReturnsFalseIfTagsAndFilterNotMatches()
    {
        $data = [
            'Tags' => [
                ['Key' => 'testKey', 'Value' => 'testValue'],
                ['Key' => 'testKey2', 'Value' => 'testValue2']
            ]
        ];
        $stub = $this->getMockForAbstractClass('\AwsInspector\Model\AbstractResource', [$data]);
        $filter = [
            'xxx' => 'yyy'
        ];

        $this->assertFalse($stub->matchesTags($filter));
    }
}
