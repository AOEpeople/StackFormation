<?php

namespace AwsInspector\Tests\Model\Ec2;

use AwsInspector\Model\Ec2\Instance;

class InstanceTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @test
     */
    public function sshConnectionTest()
    {
        $instance = new Instance([
            'Tags' => [],
            'PrivateIpAddress' => '4.5.7.6'
        ]);
        $connection = $instance->getSshConnection();
        $this->assertEquals('4.5.7.6', $connection->getHost());
        $this->assertEquals('ec2-user', $connection->getUsername());
    }

    /**
     * @test
     */
    public function user()
    {
        $instance = new Instance([
            'Tags' => [['Key' => 'inspector', 'Value' => 'User:Foo']],
            'PrivateIpAddress' => '4.5.7.6',
        ]);
        $this->assertEquals('User:Foo', $instance->getTag('inspector'));
        $connection = $instance->getSshConnection();
        $this->assertEquals('4.5.7.6', $connection->getHost());
        $this->assertEquals('Foo', $connection->getUsername());
    }

    /**
     * @test
     */
    public function sshConnectionTestViaBastion()
    {
        $instance = $this->getMock('AwsInspector\Model\Ec2\Instance', ['getJumpHost'], [[
            'Tags' => [
                ['Key' => 'inspector', 'Value' => 'User:Foo,Type:Bastion,Environment:dpl']
            ],
            'PrivateIpAddress' => '4.5.7.6',
        ]]);
        $instance->method('getJumpHost')->willReturn(new Instance([
            'Tags' => [['Key' => 'inspector', 'Value' => 'User:Bar']],
            'PrivateIpAddress' => '1.2.3.4',
        ]));

        /* @var $instance Instance */
        $this->assertEquals('User:Foo,Type:Bastion,Environment:dpl', $instance->getTag('inspector'));

        $connection = $instance->getSshConnection();
        $this->assertEquals('4.5.7.6', $connection->getHost());
        $this->assertEquals('Foo', $connection->getUsername());

        $proxyCommand = "ssh -o ConnectTimeout=5 -o LogLevel=ERROR -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null Bar@1.2.3.4 'nc %h %p'";
        $command = 'ssh -o ProxyCommand="'.$proxyCommand.'" -o ConnectTimeout=5 -o LogLevel=ERROR -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null Foo@4.5.7.6';

        $this->assertEquals($command, $connection->__toString());
    }

    public function exec()
    {
        $instance = new Instance([
            'Tags' => [],
            'PrivateIpAddress' => '1.2.3.4',
        ]);

        $instance->exec();
    }

}
