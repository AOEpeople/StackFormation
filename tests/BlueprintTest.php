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

    /**
     * @test
     */
    public function getConditionalParameterValueDefault()
    {
        $config = new \StackFormation\Config([FIXTURE_ROOT.'/Config/blueprint.conditional_value.yml']);
        $cfnClientMock = $this->getMock('\Aws\CloudFormation\CloudFormationClient', [], [], '', false);
        $resolverMock = $this->getMock('\StackFormation\PlaceholderResolver', [], [], '', false);
        $blueprintFactory = new \StackFormation\BlueprintFactory($cfnClientMock, $config, $resolverMock);
        $blueprint = $blueprintFactory->getBlueprint('conditional_value');
        $parameters = $blueprint->getParameters(true, true);
        $this->assertArrayHasKey('CondValue', $parameters);
        $this->assertEquals('c', $parameters['CondValue']);
    }

    /**
     * @test
     */
    public function getConditionalParameterValueEnvFooVal1()
    {
        putenv('Foo=Val1');
        $config = new \StackFormation\Config([FIXTURE_ROOT.'/Config/blueprint.conditional_value.yml']);
        $cfnClientMock = $this->getMock('\Aws\CloudFormation\CloudFormationClient', [], [], '', false);
        $resolverMock = $this->getMock('\StackFormation\PlaceholderResolver', [], [], '', false);
        $blueprintFactory = new \StackFormation\BlueprintFactory($cfnClientMock, $config, $resolverMock);
        $blueprint = $blueprintFactory->getBlueprint('conditional_value');
        $parameters = $blueprint->getParameters(true, true);
        $this->assertArrayHasKey('CondValue', $parameters);
        $this->assertEquals('a', $parameters['CondValue']);
    }

    /**
     * @test
     */
    public function getConditionalParameterValueEnvFooVal2()
    {
        putenv('Foo=Val2');
        $config = new \StackFormation\Config([FIXTURE_ROOT.'/Config/blueprint.conditional_value.yml']);
        $cfnClientMock = $this->getMock('\Aws\CloudFormation\CloudFormationClient', [], [], '', false);
        $resolverMock = $this->getMock('\StackFormation\PlaceholderResolver', [], [], '', false);
        $blueprintFactory = new \StackFormation\BlueprintFactory($cfnClientMock, $config, $resolverMock);
        $blueprint = $blueprintFactory->getBlueprint('conditional_value');
        $parameters = $blueprint->getParameters(true, true);
        $this->assertArrayHasKey('CondValue', $parameters);
        $this->assertEquals('b', $parameters['CondValue']);
    }

}