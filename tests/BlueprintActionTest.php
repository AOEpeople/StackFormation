<?php

class BlueprintActionTest extends PHPUnit_Framework_TestCase {


    /** @var PHPUnit_Framework_MockObject_MockObject */
    protected $profileManagerMock;

    /** @var PHPUnit_Framework_MockObject_MockObject */
    protected $cfnClientMock;

    /** @var PHPUnit_Framework_MockObject_MockObject */
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
    }

    public function testBeforeScriptsAreBeingExecutedWhenDeploying()
    {
        $this->blueprintMock->expects($this->once())->method('executeBeforeScripts');

        $blueprintAction = new \StackFormation\BlueprintAction(
            $this->blueprintMock,
            $this->profileManagerMock
        );

        $blueprintAction->deploy(false);
    }

    public function testBeforeScriptsAreNotBeingExecutedWhenDeployingWithDryRun()
    {
        $this->blueprintMock->expects($this->never())->method('executeBeforeScripts');

        $blueprintAction = new \StackFormation\BlueprintAction(
            $this->blueprintMock,
            $this->profileManagerMock
        );
        $blueprintAction->deploy(true);

    }

    public function testBeforeScriptsAreBeingExecutedWhenRequestingChangeSet()
    {
        $this->blueprintMock->expects($this->once())->method('executeBeforeScripts');

        $this->cfnClientMock->method('describeChangeSet')->willReturn(new \Aws\Result(['Status' => 'CREATE_COMPLETE']));

        $blueprintAction = new \StackFormation\BlueprintAction(
            $this->blueprintMock,
            $this->profileManagerMock
        );
        $blueprintAction->getChangeSet();
    }

    public function testFailingChangeSet()
    {
        $this->blueprintMock->expects($this->once())->method('executeBeforeScripts');

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


}