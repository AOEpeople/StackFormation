<?php

class BlueprintActionTest extends PHPUnit_Framework_TestCase {

    public function testBeforeScriptsAreBeingExecuted()
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

}