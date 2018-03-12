<?php

namespace StackFormation\Tests\PreProcessor\Stage\Tree;

class ExpandCslPortTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function expandCslPort()
    {
        $granparentData = [
            'granparent' => [
                'IpProtocol' => 'tcp',
                'Port' => '80,443',
                'CidrIp' => '1.1.1.1/1'
            ]
        ];
        $parentData = $granparentData['granparent'];

        $parentRecursiveArrayObject = new \StackFormation\PreProcessor\RecursiveArrayObject($parentData, \ArrayObject::ARRAY_AS_PROPS);
        $gradnparentRecursiveArrayObject = new \StackFormation\PreProcessor\RecursiveArrayObject($granparentData, \ArrayObject::ARRAY_AS_PROPS);

        $parentRootlineItem = $this->getMockBuilder('\StackFormation\PreProcessor\RootlineItem')
            ->disableOriginalConstructor()
            ->setMethods(['getValue', 'getKey'])
            ->getMock();
        $parentRootlineItem->method('getValue')->willReturn($parentRecursiveArrayObject);
        $parentRootlineItem->method('getKey')->willReturn('granparent');

        $grandParentRootlineItem = $this->getMockBuilder('\StackFormation\PreProcessor\RootlineItem')
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMock();
        $grandParentRootlineItem->method('getValue')->willReturn($gradnparentRecursiveArrayObject);

        $rootline = $this->getMockBuilder('\StackFormation\PreProcessor\Rootline')
            ->setMethods(['parent'])
            ->getMock();
        $rootline->expects($this->any())
            ->method('parent')
            ->with($this->logicalOr(
                $this->equalTo(1),
                $this->equalTo(2)
            ))
            ->will($this->returnCallback(
                function($param) use ($parentRootlineItem, $grandParentRootlineItem) {
                    print_r($param);
                    if ($param == 1) return $parentRootlineItem;
                    if ($param == 2) return $grandParentRootlineItem;
                }
            ));

        $treePreProcessor = $this->getMock('\StackFormation\PreProcessor\TreePreProcessor', [], [], '', false);
        $transformer = new \StackFormation\PreProcessor\Stage\Tree\ExpandCslPort($treePreProcessor);

        $output = $transformer->invoke('/Resources/InstanceSecurityGroup/Properties/SecurityGroupIngress/1/Port', '80,443', $rootline);
        $this->assertTrue($output);

        $grandparentData = $rootline->parent(2)->getValue()->getArrayCopy();

        $this->assertSame(2, count($grandparentData));
        $this->assertSame('80', $grandparentData[0]['FromPort']);
        $this->assertSame('80', $grandparentData[0]['ToPort']);
        $this->assertSame('443', $grandparentData[1]['FromPort']);
        $this->assertSame('443', $grandparentData[1]['ToPort']);
    }
}
