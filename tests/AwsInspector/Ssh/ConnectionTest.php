<?php

namespace AwsInspector\Tests\Ssh;

use AwsInspector\Model\Ec2\Instance;
use AwsInspector\Ssh\Connection;
use AwsInspector\Ssh\PrivateKey;

class ConnectionTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @test
     */
    public function simple()
    {
        $connection = new Connection('TestUsername', '1.2.3.4');
        $this->assertEquals(
            'ssh -o ConnectTimeout=5 -o LogLevel=ERROR -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null TestUsername@1.2.3.4',
            $connection->__toString()
        );
    }

    /**
     * @test
     */
    public function withPrivateKey()
    {
        $connection = new Connection('TestUsername', '1.2.3.4', PrivateKey::get(FIXTURE_ROOT . 'foo.pem'));
        $this->assertEquals(
            'ssh -i '.FIXTURE_ROOT.'foo.pem -o ConnectTimeout=5 -o LogLevel=ERROR -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null TestUsername@1.2.3.4',
            $connection->__toString()
        );
    }

    /**
     * @test
     */
    public function withJumpHost()
    {
        $jumpHost = new Instance([
            'Tags' => [],
            'PrivateIpAddress' => '4.5.7.6'
        ]);
        $connection = new Connection('TestUsername', '1.2.3.4', null, $jumpHost);
        $this->assertEquals(
            'ssh -o ProxyCommand="ssh -o ConnectTimeout=5 -o LogLevel=ERROR -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null ec2-user@4.5.7.6 \'nc %h %p\'" -o ConnectTimeout=5 -o LogLevel=ERROR -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null TestUsername@1.2.3.4',
            $connection->__toString()
        );
    }

}
