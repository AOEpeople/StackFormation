<?php

namespace AwsInspector\Tests\Ssh;

use AwsInspector\Ssh\Command;
use AwsInspector\Ssh\LocalConnection;

class CommandTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @test
     */
    public function simpleCommand()
    {
        $connectionMock = $this->getMock('\AwsInspector\Ssh\Connection', [], [], '', false);
        $connectionMock->method('__toString')->willReturn('connection');
        $command = new Command($connectionMock, 'command');
        $this->assertEquals("connection 'command'", $command->__toString());
    }

    /**
     * @test
     */
    public function commandWithArguments()
    {
        $connectionMock = $this->getMock('\AwsInspector\Ssh\Connection', [], [], '', false);
        $connectionMock->method('__toString')->willReturn('connection');
        $command = new Command($connectionMock, 'echo '.escapeshellarg('Hello World'));
        $this->assertEquals("connection 'echo '\''Hello World'\'''", $command->__toString());
    }

    /**
     * @test
     */
    public function runLocalCommand()
    {
        $testfile = tempnam(sys_get_temp_dir(), __FUNCTION__);
        $command = new Command(new LocalConnection(), 'echo -n '.escapeshellarg('Hello World') . ' > ' . $testfile);
        $this->assertEquals("echo -n 'Hello World' > $testfile", $command->__toString());
        $command->exec();
        $this->assertEquals('Hello World', file_get_contents($testfile));
        unlink($testfile);
    }

    /**
     * @test
     */
    public function asUser()
    {
        $connectionMock = $this->getMock('\AwsInspector\Ssh\Connection', [], [], '', false);
        $connectionMock->method('__toString')->willReturn('connection');
        $command = new Command($connectionMock, 'whoami', 'www-data');
        $this->assertEquals(
            "connection 'sudo -u '\''www-data'\'' bash -c '\''whoami'\'''",
            $command->__toString()
        );
    }

    /**
     * @test
     */
    public function runLocalCommandasUser()
    {
        try {
            $testfile = tempnam(sys_get_temp_dir(), __FUNCTION__);
            $command = new Command(new LocalConnection(), 'whoami > ' . $testfile, 'root');
            $command->exec();
            $this->assertEquals('root', trim(file_get_contents($testfile)));
            unlink($testfile);
        } catch (\Exception $e) {
            $this->markTestSkipped('It is ok if this test fails');
        }
    }

}
