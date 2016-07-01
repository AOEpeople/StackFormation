<?php

namespace StackFormation\Tests;

class StackTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \StackFormation\Stack
     */
    protected $stack;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject<\Aws\CloudFormation\CloudFormationClient>
     */
    protected $cfnClientMock;

    public function setUp()
    {
        $describeStackResourcesResult = new \Aws\Result(json_decode(file_get_contents(FIXTURE_ROOT.'Stack/test-stack1.describeStackResources.json'), true));

        $this->cfnClientMock = $this->getMock('\Aws\CloudFormation\CloudFormationClient', ['describeStackResources'], [], '', false);
        $this->cfnClientMock
            ->method('describeStackResources')
            ->with(['StackName' => 'test-stack1'])
            ->willReturn($describeStackResourcesResult);

        $data = file_get_contents(FIXTURE_ROOT.'Stack/test-stack1.json');
        $data = json_decode($data, true);
        $this->stack = new \StackFormation\Stack($data, $this->cfnClientMock);
    }

    public function testName()
    {
        $this->assertEquals('test-stack1', $this->stack->getName());
    }

    public function testDescription()
    {
        $this->assertEquals('Test Description', $this->stack->getDescription());
    }

    public function testStatus()
    {
        $this->assertEquals('UPDATE_COMPLETE', $this->stack->getStatus());
    }

    public function testParameter()
    {
        $this->assertEquals('Bar', $this->stack->getParameter('Foo'));
    }

    public function testParameterThatDoesntExist()
    {
        $this->setExpectedException('Exception', "Parameter 'DoesNotExist' not found in stack 'test-stack1'");
        $this->stack->getParameter('DoesNotExist');
    }

    public function testTag()
    {
        $this->assertEquals('BarTag', $this->stack->getTag('FooTag'));
    }

    public function testTagThatDoesntExist()
    {
        $this->setExpectedException('Exception', "Tag 'DoesNotExist' not found in stack 'test-stack1'");
        $this->stack->getTag('DoesNotExist');
    }

    public function testOutput()
    {
        $this->assertEquals('OutputBar', $this->stack->getOutput('OutputFoo'));
    }

    public function testOutputThatDoesntExist()
    {
        $this->setExpectedException('Exception', "Output 'DoesNotExist' not found in stack 'test-stack1'");
        $this->stack->getOutput('DoesNotExist');
    }

    public function testResource()
    {
        $this->assertEquals('sg-de4494b8', $this->stack->getResource('InstanceSg'));
    }

    public function testResourceThatDoesntExist()
    {
        $this->setExpectedException('Exception', "Resource 'DoesNotExist' not found in stack 'test-stack1'");
        $this->stack->getResource('DoesNotExist');
    }
}
