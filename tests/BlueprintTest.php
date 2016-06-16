<?php

class BlueprintTest extends PHPUnit_Framework_TestCase {

    /**
     * @test
     */
    public function getVariable()
    {
        $config = new \StackFormation\Config([FIXTURE_ROOT.'/Config/blueprint.1.yml']);
        $cfnClientMock = $this->getMock('\Aws\CloudFormation\CloudFormationClient', [], [], '', false);
        $resolverMock = $this->getMock('\StackFormation\PlaceholderResolver', [], [], '', false);
        $blueprintFactory = new \StackFormation\BlueprintFactory($cfnClientMock, $config, $resolverMock);
        $blueprintVars = $blueprintFactory->getBlueprint('fixture1')->getVars();
        $this->assertArrayHasKey('BlueprintFoo', $blueprintVars);
        $this->assertEquals('BlueprintBar', $blueprintVars['BlueprintFoo']);
    }

}