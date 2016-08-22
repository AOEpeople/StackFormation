<?php

namespace AwsInspector\Tests\Model\AutoScaling;
use AwsInspector\Tests\MockFacade;

class RepositoryTest extends MockFacade
{
    /**
     * @param array $methods
     * @return \PHPUnit_Framework_MockObject_MockObject<\StackFormation\Profile\Manager>
     */
    public function getProfileManagerMock(array $methods)
    {
        return $this->getMock('\StackFormation\Profile\Manager', $methods, [], '', false);
    }

    /**
     * @param array $methods
     * @return \PHPUnit_Framework_MockObject_MockObject<\Aws\AutoScaling\AutoScalingClient>
     */
    public function getAutoScalingGroupClientMock(array $methods)
    {
        return $this->getMock('\Aws\AutoScaling\AutoScaling', $methods, [], '', false);
    }

    /**
     * @test
     */
    public function findAutoScalingGroupsReturnsExpectedCollection()
    {
        $methods = ['describeAutoScalingGroups'];
        $autoScalingClient = $this->getAutoScalingGroupClientMock($methods);
        $autoScalingClient->method('describeAutoScalingGroups')->willReturn(new \Aws\Result(
            [
                'AutoScalingGroups' => [
                    [
                        'AutoScalingGroupARN' => 'arn:1234',
                        'AutoScalingGroupName' => 'GroupTest1'
                    ],
                    [
                        'AutoScalingGroupARN' => 'arn:5678',
                        'AutoScalingGroupName' => 'GroupTest2'
                    ],
                    [
                        'AutoScalingGroupARN' => 'arn:98765',
                        'AutoScalingGroupName' => 'GroupTest3'
                    ]
                ]
            ]
        ));

        $profileManager = $this->getProfileManagerMock(['getClient']);
        $profileManager->method('getClient')->willReturn($autoScalingClient);

        $autoScalingRepository = new \AwsInspector\Model\AutoScaling\Repository('', $profileManager);
        $result = $autoScalingRepository->findAutoScalingGroups();

        $this->assertInstanceOf('\AwsInspector\Model\Collection', $result);
        $this->assertSame(3, $result->count());
    }

    /**
     * @test
     */
    public function findAutoScalingGroupsByTagsReturnsExpectedCollection()
    {
        $methods = ['describeAutoScalingGroups'];
        $autoScalingClient = $this->getAutoScalingGroupClientMock($methods);
        $autoScalingClient->method('describeAutoScalingGroups')->willReturn(new \Aws\Result(
            [
                'AutoScalingGroups' => [
                    [
                        'AutoScalingGroupARN' => 'arn:1234',
                        'AutoScalingGroupName' => 'GroupTest1',
                        'Tags' => [
                            [
                                'Key' => 'GroupTest1TagKey1',
                                'Value' => 'GroupTest1TagKey2'
                            ]
                        ]
                    ],
                    [
                        'AutoScalingGroupARN' => 'arn:5678',
                        'AutoScalingGroupName' => 'GroupTest2',
                        'Tags' => [
                            [
                                'Key' => 'GroupTest2TagKey1',
                                'Value' => 'GroupTest2TagKey2'
                            ]
                        ]
                    ],
                    [
                        'AutoScalingGroupARN' => 'arn:98765',
                        'AutoScalingGroupName' => 'GroupTest3',
                        'Tags' => [
                            [
                                'Key' => 'GroupTest3TagKey1',
                                'Value' => 'GroupTest3TagKey2'
                            ]
                        ]
                    ]
                ]
            ]
        ));

        $profileManager = $this->getProfileManagerMock(['getClient']);
        $profileManager->method('getClient')->willReturn($autoScalingClient);

        $autoScalingRepository = new \AwsInspector\Model\AutoScaling\Repository('', $profileManager);
        $result = $autoScalingRepository->findAutoScalingGroupsByTags(['GroupTest3TagKey1' => 'GroupTest3TagKey2']);

        $this->assertInstanceOf('\AwsInspector\Model\Collection', $result);
        $this->assertSame(1, $result->count());
    }

    /**
     * @test
     */
    public function findByAutoScalingGroupNameReturnsExpectedCollection()
    {
        $methods = ['describeAutoScalingGroups'];
        $autoScalingClient = $this->getAutoScalingGroupClientMock($methods);
        $autoScalingClient->method('describeAutoScalingGroups')->willReturn(new \Aws\Result(
            [
                'AutoScalingGroups' => [
                    [
                        'AutoScalingGroupARN' => 'arn:1234',
                        'AutoScalingGroupName' => 'GroupDummy-One-Asg'
                    ],
                    [
                        'AutoScalingGroupARN' => 'arn:5678',
                        'AutoScalingGroupName' => 'GroupTest-One-Asg'
                    ],
                    [
                        'AutoScalingGroupARN' => 'arn:5465',
                        'AutoScalingGroupName' => 'GroupTest-Two-Asg'
                    ]
                ]
            ]
        ));

        $profileManager = $this->getProfileManagerMock(['getClient']);
        $profileManager->method('getClient')->willReturn($autoScalingClient);

        $autoScalingRepository = new \AwsInspector\Model\AutoScaling\Repository('', $profileManager);
        $result = $autoScalingRepository->findByAutoScalingGroupName('/^GroupTest-.*-Asg/');

        $this->assertInstanceOf('\AwsInspector\Model\Collection', $result);
        $this->assertSame(2, $result->count());
        $this->assertSame('GroupTest-One-Asg', $result->getFirst()->getAutoScalingGroupName());
    }

    /**
     * @test
     */
    public function findLaunchConfigurationsReturnsExpectedCollection()
    {
        $methods = ['describeLaunchConfigurations'];
        $autoScalingClient = $this->getAutoScalingGroupClientMock($methods);
        $autoScalingClient->method('describeLaunchConfigurations')->willReturn(new \Aws\Result(
            [
                'LaunchConfigurations' => [
                    [
                        'LaunchConfigurationName' => 'TestLaunchConfiguration'
                    ],
                    [
                        'LaunchConfigurationName' => 'TestLaunchConfiguration2'
                    ]
                ]
            ]
        ));

        $profileManager = $this->getProfileManagerMock(['getClient']);
        $profileManager->method('getClient')->willReturn($autoScalingClient);

        $autoScalingRepository = new \AwsInspector\Model\AutoScaling\Repository('', $profileManager);
        $result = $autoScalingRepository->findLaunchConfigurations();

        $this->assertInstanceOf('\AwsInspector\Model\Collection', $result);
        $this->assertSame(2, $result->count());
    }

    /**
     * @test
     */
    public function findLaunchConfigurationsGroupedByImageIdReturnsExpectedArray()
    {
        $methods = ['describeLaunchConfigurations'];
        $autoScalingClient = $this->getAutoScalingGroupClientMock($methods);
        $autoScalingClient->method('describeLaunchConfigurations')->willReturn(new \Aws\Result(
            [
                'LaunchConfigurations' => [
                    [
                        'LaunchConfigurationName' => 'TestLaunchConfiguration',
                        'ImageId' => 'x1234'
                    ],
                    [
                        'LaunchConfigurationName' => 'TestLaunchConfiguration2',
                        'ImageId' => 'y8765'
                    ]
                ]
            ]
        ));

        $profileManager = $this->getProfileManagerMock(['getClient']);
        $profileManager->method('getClient')->willReturn($autoScalingClient);

        $autoScalingRepository = new \AwsInspector\Model\AutoScaling\Repository('', $profileManager);
        $result = $autoScalingRepository->findLaunchConfigurationsGroupedByImageId();

        $this->assertArrayHasKey('x1234', $result);
        $this->assertArrayHasKey('y8765', $result);
        $this->assertSame(2, count($result));
    }
}
