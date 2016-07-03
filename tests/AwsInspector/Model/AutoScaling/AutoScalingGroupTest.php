<?php

namespace AwsInspector\Tests\Model\AutoScaling;

class AutoScalingGroupTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param array $methods
     * @return \PHPUnit_Framework_MockObject_MockObject<\StackFormation\Profile\Manager>
     */
    public function getProfileManagerMock(array $methods)
    {
        return $this->getMockBuilder('\StackFormation\Profile\Manager')
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }

    /**
     * @param array $methods
     * @return \PHPUnit_Framework_MockObject_MockObject<\Aws\AutoScaling\AutoScalingClient>
     */
    public function getAutoScalingGroupClientMock(array $methods)
    {
        return $this->getMockBuilder('\Aws\AutoScaling\AutoScalingClient')
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }

    /**
     * @test
     */
    public function attachLoadBalancersReturnsExpectedResult()
    {
        $autoScalingGroupClient = $this->getAutoScalingGroupClientMock(['attachLoadBalancers']);
        $autoScalingGroupClient->method('attachLoadBalancers')->willReturn(new \Aws\Result([]));

        $profileManager = $this->getProfileManagerMock(['getClient']);
        $profileManager->method('getClient')->willReturn($autoScalingGroupClient);

        $data = ['AutoScalingGroupName' => 'MyASGName'];
        $autoScalingGroup = new \AwsInspector\Model\AutoScaling\AutoScalingGroup($data, $profileManager);

        $loadBalancerObject = $this->getMockBuilder('\AwsInspector\Model\Elb\Elb')
            ->disableOriginalConstructor()
            ->setMethods(['getLoadBalancerName'])
            ->getMock();
        $loadBalancerObject->method('getLoadBalancerName')->willReturn('LoadBalancer3');

        $loadBalancerNames = ['LoadBalancer1', 'LoadBalancer2', $loadBalancerObject];

        // The results for this operation are always empty. (AWS)
        $result = $autoScalingGroup->attachLoadBalancers($loadBalancerNames);
        $this->assertInstanceOf('\Aws\Result', $result);
    }

    /**
     * @test
     */
    public function attachLoadBalancersThrowsExceptionIfLoadBalancerArrayIsNotAValidArgument()
    {
        $autoScalingGroupClient = $this->getAutoScalingGroupClientMock(['attachLoadBalancers']);
        $autoScalingGroupClient->method('attachLoadBalancers')->willReturn(new \Aws\Result([]));

        $profileManager = $this->getProfileManagerMock(['getClient']);
        $profileManager->method('getClient')->willReturn($autoScalingGroupClient);

        $data = ['AutoScalingGroupName' => 'MyASGName'];
        $autoScalingGroup = new \AwsInspector\Model\AutoScaling\AutoScalingGroup($data, $profileManager);

        $loadBalancerNames = [11];

        $this->setExpectedException('InvalidArgumentException', 'Argument must be an array of strings or \AwsInspector\Model\Elb\Elb objects');
        $autoScalingGroup->attachLoadBalancers($loadBalancerNames);
    }

    /**
     * @test
     */
    public function detachLoadBalancersReturnsExpectedResult()
    {
        $autoScalingGroupClient = $this->getAutoScalingGroupClientMock(['detachLoadBalancers']);
        $autoScalingGroupClient->method('detachLoadBalancers')->willReturn(new \Aws\Result([]));

        $profileManager = $this->getProfileManagerMock(['getClient']);
        $profileManager->method('getClient')->willReturn($autoScalingGroupClient);

        $data = ['AutoScalingGroupName' => 'MyASGName'];
        $autoScalingGroup = new \AwsInspector\Model\AutoScaling\AutoScalingGroup($data, $profileManager);

        $loadBalancerNames = ['LoadBalancer1', 'LoadBalancer2'];

        // The results for this operation are always empty. (AWS)
        $result = $autoScalingGroup->detachLoadBalancers($loadBalancerNames);
        $this->assertInstanceOf('\Aws\Result', $result);
    }

    /**
     * @test
     */
    public function suspendProcessesSuspendAllProcesses()
    {
        $autoScalingGroupClient = $this->getAutoScalingGroupClientMock(['suspendProcesses']);
        $autoScalingGroupClient->method('suspendProcesses')->willReturn(new \Aws\Result([]));

        $profileManager = $this->getProfileManagerMock(['getClient']);
        $profileManager->method('getClient')->willReturn($autoScalingGroupClient);

        $data = ['AutoScalingGroupName' => 'MyASGName'];
        $autoScalingGroup = new \AwsInspector\Model\AutoScaling\AutoScalingGroup($data, $profileManager);

        // The results for this operation are always empty. (AWS)
        $autoScalingGroup->suspendProcesses('all');
    }

    /**
     * @test
     */
    public function validateProcessesParamThrowsExpectionIfArgumentIsAStringButNotEqualsAll()
    {
        $autoScalingGroupClient = $this->getAutoScalingGroupClientMock(['suspendProcesses']);
        $autoScalingGroupClient->method('suspendProcesses')->willReturn(new \Aws\Result([]));

        $profileManager = $this->getProfileManagerMock(['getClient']);
        $profileManager->method('getClient')->willReturn($autoScalingGroupClient);

        $data = ['AutoScalingGroupName' => 'MyASGName'];
        $autoScalingGroup = new \AwsInspector\Model\AutoScaling\AutoScalingGroup($data, $profileManager);

        $this->setExpectedException('Exception', 'Argument must be "all" or an array of processes');
        $autoScalingGroup->suspendProcesses('doenstExist');
    }

    /**
     * @test
     */
    public function validateProcessesParamThrowsExpectionIfArgumentIsAArrayWithInvalidProccesses()
    {
        $autoScalingGroupClient = $this->getAutoScalingGroupClientMock(['suspendProcesses']);
        $autoScalingGroupClient->method('suspendProcesses')->willReturn(new \Aws\Result([]));

        $profileManager = $this->getProfileManagerMock(['getClient']);
        $profileManager->method('getClient')->willReturn($autoScalingGroupClient);

        $data = ['AutoScalingGroupName' => 'MyASGName'];
        $autoScalingGroup = new \AwsInspector\Model\AutoScaling\AutoScalingGroup($data, $profileManager);

        $reflection = new \ReflectionClass($autoScalingGroup);
        $reflection_property = $reflection->getProperty('availableProcesses');
        $reflection_property->setAccessible(true);
        $reflection_property->setValue($autoScalingGroup, ['test']);

        $this->setExpectedException('Exception', "Process 'doesntExist' is invalid'");
        $autoScalingGroup->suspendProcesses(['doesntExist']);
    }

    /**
     * @test
     */
    public function resumeProcessesResumedAllProcesses()
    {
        $autoScalingGroupClient = $this->getAutoScalingGroupClientMock(['resumeProcesses']);
        $autoScalingGroupClient->method('resumeProcesses')->willReturn(new \Aws\Result([]));

        $profileManager = $this->getProfileManagerMock(['getClient']);
        $profileManager->method('getClient')->willReturn($autoScalingGroupClient);

        $data = ['AutoScalingGroupName' => 'MyASGName'];
        $autoScalingGroup = new \AwsInspector\Model\AutoScaling\AutoScalingGroup($data, $profileManager);

        // The results for this operation are always empty. (AWS)
        $autoScalingGroup->resumeProcesses('all');
    }
}
