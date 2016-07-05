<?php

namespace StackFormation\Tests;

class BlueprintActionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $profileManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $cfnClientMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $blueprintMock;

    public function setUp()
    {
        parent::setUp();

        $this->cfnClientMock = $this->getMock('\Aws\CloudFormation\CloudFormationClient', ['createChangeSet', 'UpdateStack', 'DescribeChangeSet', 'ValidateTemplate'], [], '', false);
        $this->cfnClientMock->method('createChangeSet')->willReturn(new \Aws\Result(['id' => 'foo_id']));

        $this->profileManagerMock = $this->getMock('\StackFormation\Profile\Manager', [], [], '', false);
        $this->profileManagerMock->method('getClient')->willReturn($this->cfnClientMock);
        $this->profileManagerMock->method('getStackFactory')->willReturnCallback(function () {
            $stackFactoryMock = $this->getMock('\StackFormation\StackFactory', [], [], '', false);
            $stackFactoryMock->method('getStackStatus')->willReturn('CREATE_COMPLETE');
            return $stackFactoryMock;
        });

        $this->blueprintMock = $this->getMock('\StackFormation\Blueprint', [], [], '', false);
        $this->blueprintMock->method('getBlueprintReference')->willReturn('FOO');
        $this->blueprintMock->method('getTags')->willReturn([]);
        $this->blueprintMock->method('getBeforeScript')->willReturn([]);
    }

    public function testFailingChangeSet()
    {
        $this->cfnClientMock->method('describeChangeSet')->willReturn(new \Aws\Result(['Status' => 'FAILED', 'StatusReason' => 'FOO REASON']));

        $this->setExpectedException('Exception', 'FOO REASON');

        $blueprintAction = new \StackFormation\BlueprintAction(
            $this->blueprintMock,
            $this->profileManagerMock
        );
        $blueprintAction->getChangeSet();
    }

    public function testValidateTemplate()
    {
        $this->cfnClientMock->expects($this->once())->method('validateTemplate');

        $blueprintAction = new \StackFormation\BlueprintAction(
            $this->blueprintMock,
            $this->profileManagerMock
        );
        $blueprintAction->validateTemplate();
    }

    /**
     * @test
     */
    public function runBeforeScripts()
    {
        $testfile = tempnam(sys_get_temp_dir(), __FUNCTION__);

        $blueprintMock = $this->getMock('\StackFormation\Blueprint', [], [], '', false);
        $blueprintMock->method('getBlueprintReference')->willReturn('FOO');
        $blueprintMock->method('getBasePath')->willReturn(sys_get_temp_dir());
        $blueprintMock->method('getBeforeScript')->willReturn('echo -n "HELLO WORLD" > ' . $testfile);

        $blueprintAction = new \StackFormation\BlueprintAction(
            $blueprintMock,
            $this->profileManagerMock
        );

        $blueprintAction->executeBeforeScript();

        $this->assertStringEqualsFile($testfile, 'HELLO WORLD');
        unlink($testfile);
    }

    /**
     * @test
     */
    public function runBeforeScriptsInCorrectLocation()
    {
        $testfile = tempnam(sys_get_temp_dir(), __FUNCTION__);

        $blueprintMock = $this->getMock('\StackFormation\Blueprint', [], [], '', false);
        $blueprintMock->method('getBlueprintReference')->willReturn('FOO');
        $blueprintMock->method('getBasePath')->willReturn(FIXTURE_ROOT.'RunBeforeScript');
        $blueprintMock->method('getBeforeScript')->willReturn('cat foo.txt > ' . $testfile);

        $blueprintAction = new \StackFormation\BlueprintAction(
            $blueprintMock,
            $this->profileManagerMock
        );

        $blueprintAction->executeBeforeScript();

        $this->assertStringEqualsFile($testfile, 'HELLO WORLD FROM FILE');
        unlink($testfile);
    }

    /**
     * @test
     */
    public function beforeScriptsHaveProfilesEnvVarsSet()
    {
        chdir(FIXTURE_ROOT.'ProfileManager/fixture_before_scripts');
        $testfile = tempnam(sys_get_temp_dir(), __FUNCTION__);

        $profileManager = new \StackFormation\Profile\Manager();

        $blueprintMock = $this->getMock('\StackFormation\Blueprint', [], [], '', false);
        $blueprintMock->method('getProfile')->willReturn('before_scripts_profile');
        $blueprintMock->method('getBlueprintReference')->willReturn('FOO');
        $blueprintMock->method('getBasePath')->willReturn(sys_get_temp_dir());
        $blueprintMock->method('getBeforeScript')->willReturn('echo -n "${AWS_ACCESS_KEY_ID}:${AWS_SECRET_ACCESS_KEY}" > ' . $testfile);

        $blueprintAction = new \StackFormation\BlueprintAction($blueprintMock, $profileManager);
        $blueprintAction->executeBeforeScript();

        $this->assertStringEqualsFile($testfile, 'TESTACCESSKEY1:TESTSECRETKEY1');
        unlink($testfile);
    }

    /**
     * @test
     */
    public function beforeScriptsThatFail()
    {
        $this->setExpectedException('Exception', 'Error executing script');

        chdir(FIXTURE_ROOT.'ProfileManager/fixture_before_scripts');

        $profileManager = new \StackFormation\Profile\Manager();

        $blueprintMock = $this->getMock('\StackFormation\Blueprint', [], [], '', false);
        $blueprintMock->method('getProfile')->willReturn('before_scripts_profile');
        $blueprintMock->method('getBlueprintReference')->willReturn('FOO');
        $blueprintMock->method('getBasePath')->willReturn(sys_get_temp_dir());
        $blueprintMock->method('getBeforeScript')->willReturn('exit 1');

        $blueprintAction = new \StackFormation\BlueprintAction($blueprintMock, $profileManager);
        $blueprintAction->executeBeforeScript();
    }

    /**
     * @test
     */
    public function beforeScriptsWhereCommandInBetweenFails()
    {
        $this->setExpectedException('Exception', 'Error executing script');

        chdir(FIXTURE_ROOT.'ProfileManager/fixture_before_scripts');

        $profileManager = new \StackFormation\Profile\Manager();

        $blueprintMock = $this->getMock('\StackFormation\Blueprint', [], [], '', false);
        $blueprintMock->method('getProfile')->willReturn('before_scripts_profile');
        $blueprintMock->method('getBlueprintReference')->willReturn('FOO');
        $blueprintMock->method('getBasePath')->willReturn(sys_get_temp_dir());
        $blueprintMock->method('getBeforeScript')->willReturn(
            'echo "Hello World"' . "\n" .
            '(exit 1)' . "\n" .
            'echo "Foo"'
        );

        $blueprintAction = new \StackFormation\BlueprintAction($blueprintMock, $profileManager);
        $blueprintAction->executeBeforeScript();
    }

    /**
     * @test
     */
    public function stackNameVariable()
    {
        $testfile = tempnam(sys_get_temp_dir(), __FUNCTION__);

        $blueprintMock = $this->getMock('\StackFormation\Blueprint', [], [], '', false);
        $blueprintMock->method('getBlueprintReference')->willReturn('FOO');
        $blueprintMock->method('getStackName')->willReturn('FooStackName');
        $blueprintMock->method('getBasePath')->willReturn(sys_get_temp_dir());
        $blueprintMock->method('getBeforeScript')->willReturn('echo -n "$STACKNAME" > ' . $testfile);

        $blueprintAction = new \StackFormation\BlueprintAction(
            $blueprintMock,
            $this->profileManagerMock
        );

        $blueprintAction->executeBeforeScript();

        $this->assertStringEqualsFile($testfile, 'FooStackName');
        unlink($testfile);
    }

    /**
     * @test
     */
    public function blueprintVariable()
    {
        $testfile = tempnam(sys_get_temp_dir(), __FUNCTION__);

        $blueprintMock = $this->getMock('\StackFormation\Blueprint', [], [], '', false);
        $blueprintMock->method('getBlueprintReference')->willReturn('FOO');
        $blueprintMock->method('getStackName')->willReturn('FooStackName');
        $blueprintMock->method('getName')->willReturn('FooBlueprint');
        $blueprintMock->method('getBasePath')->willReturn(sys_get_temp_dir());
        $blueprintMock->method('getBeforeScript')->willReturn('echo -n "$BLUEPRINT" > ' . $testfile);

        $blueprintAction = new \StackFormation\BlueprintAction(
            $blueprintMock,
            $this->profileManagerMock
        );

        $blueprintAction->executeBeforeScript();

        $this->assertStringEqualsFile($testfile, 'FooBlueprint');
        unlink($testfile);
    }

    /**
     * @test
     */
    public function cwdVariable()
    {
        $testfile = tempnam(sys_get_temp_dir(), __FUNCTION__);

        $blueprintMock = $this->getMock('\StackFormation\Blueprint', [], [], '', false);
        $blueprintMock->method('getBlueprintReference')->willReturn('FOO');
        $blueprintMock->method('getStackName')->willReturn('FooStackName');
        $blueprintMock->method('getBasePath')->willReturn(sys_get_temp_dir());
        $blueprintMock->method('getBeforeScript')->willReturn('echo -n "$CWD" > ' . $testfile);

        $blueprintAction = new \StackFormation\BlueprintAction(
            $blueprintMock,
            $this->profileManagerMock
        );

        $blueprintAction->executeBeforeScript();

        $this->assertStringEqualsFile($testfile, getcwd());
        unlink($testfile);
    }

    /**
     * @test
     */
    public function statusVariable()
    {
        $testfile = tempnam(sys_get_temp_dir(), __FUNCTION__);

        $blueprintMock = $this->getMock('\StackFormation\Blueprint', [], [], '', false);
        $blueprintMock->method('getBlueprintReference')->willReturn('FOO');
        $blueprintMock->method('getStackName')->willReturn('FooStackName');
        $blueprintMock->method('getBasePath')->willReturn(sys_get_temp_dir());
        $blueprintMock->method('getAfterScript')->willReturn('echo -n "$STATUS" > ' . $testfile);

        $blueprintAction = new \StackFormation\BlueprintAction(
            $blueprintMock,
            $this->profileManagerMock
        );

        $blueprintAction->executeAfterScript('FOOSTATUS');

        $this->assertStringEqualsFile($testfile, 'FOOSTATUS');
        unlink($testfile);
    }
}
