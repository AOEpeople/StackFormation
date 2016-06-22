<?php

class BlueprintActionTest extends PHPUnit_Framework_TestCase {

    public function testBeforeScriptsAreBeingExecutedWhenDeploying()
    {
        $stackFactoryMock = $this->getMock('\StackFormation\StackFactory', [], [], '', false);
        $stackFactoryMock->method('getStackStatus')->willReturn('CREATE_COMPLETE');

        $blueprintMock = $this->getMock('\StackFormation\Blueprint', [], [], '', false);
        $blueprintMock->method('getBlueprintReference')->willReturn('FOO');
        $blueprintMock->expects($this->once())->method('executeBeforeScripts');

        $cfnClientMock = $this->getMock('\Aws\CloudFormation\CloudFormationClient', [], [], '', false);
        $blueprintAction = new \StackFormation\BlueprintAction($cfnClientMock);
        $blueprintAction->deploy($blueprintMock, false, $stackFactoryMock);
    }

    public function testBeforeScriptsAreNotBeingExecutedWhenDeployingWithDryRun()
    {
        $stackFactoryMock = $this->getMock('\StackFormation\StackFactory', [], [], '', false);
        $stackFactoryMock->method('getStackStatus')->willReturn('CREATE_COMPLETE');

        $blueprintMock = $this->getMock('\StackFormation\Blueprint', [], [], '', false);
        $blueprintMock->method('getBlueprintReference')->willReturn('FOO');
        $blueprintMock->expects($this->never())->method('executeBeforeScripts');

        $cfnClientMock = $this->getMock('\Aws\CloudFormation\CloudFormationClient', [], [], '', false);
        $blueprintAction = new \StackFormation\BlueprintAction($cfnClientMock);
        $blueprintAction->deploy($blueprintMock, true, $stackFactoryMock);
    }

    public function testBeforeScriptsAreBeingExecutedWhenRequestingChangeSet()
    {
        $stackFactoryMock = $this->getMock('\StackFormation\StackFactory', [], [], '', false);
        $stackFactoryMock->method('getStackStatus')->willReturn('CREATE_COMPLETE');

        $blueprintMock = $this->getMock('\StackFormation\Blueprint', [], [], '', false);
        $blueprintMock->method('getBlueprintReference')->willReturn('FOO');
        $blueprintMock->expects($this->once())->method('executeBeforeScripts');

        $cfnClientMock = $this->getMock('\Aws\CloudFormation\CloudFormationClient', ['createChangeSet', 'describeChangeSet'], [], '', false);
        $cfnClientMock->method('createChangeSet')->willReturn(new \Aws\Result(['id' => 'foo_id']));
        $cfnClientMock->method('describeChangeSet')->willReturn(new \Aws\Result(['Status' => 'CREATE_COMPLETE']));

        $blueprintAction = new \StackFormation\BlueprintAction($cfnClientMock);
        $blueprintAction->getChangeSet($blueprintMock, false);
    }

}