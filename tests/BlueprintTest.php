<?php

class BlueprintTest extends PHPUnit_Framework_TestCase {

    protected function getMockedBlueprintFactory(\StackFormation\Config $config)
    {
        $stackFactoryMock = $this->getMock('\StackFormation\StackFactory', [], [], '', false);
        $stackFactoryMock->method('getStackOutput')->willReturn('dummyOutput');
        $stackFactoryMock->method('getStackResource')->willReturn('dummyResource');
        $stackFactoryMock->method('getStackParameter')->willReturn('dummyParameter');

        $placeholderResolver = new \StackFormation\PlaceholderResolver(
            new \StackFormation\DependencyTracker(),
            $stackFactoryMock,
            $config
        );

        $conditionalValueResolver = new \StackFormation\ConditionalValueResolver($placeholderResolver);
        return new \StackFormation\BlueprintFactory($config, $placeholderResolver, $conditionalValueResolver);
    }

    /**
     * @test
     */
    public function getVariable()
    {
        $config = new \StackFormation\Config([FIXTURE_ROOT.'/Config/blueprint.1.yml']);
        $blueprintVars = $this->getMockedBlueprintFactory($config)->getBlueprint('fixture1')->getVars();
        $this->assertArrayHasKey('BlueprintFoo', $blueprintVars);
        $this->assertEquals('BlueprintBar', $blueprintVars['BlueprintFoo']);
    }

    /**
     * @test
     */
    public function getConditionalParameterValueDefault()
    {
        putenv('Foo=Val5');
        $config = new \StackFormation\Config([FIXTURE_ROOT.'/Config/blueprint.conditional_value.yml']);
        $blueprint = $this->getMockedBlueprintFactory($config)->getBlueprint('conditional_value');
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
        $blueprint = $this->getMockedBlueprintFactory($config)->getBlueprint('conditional_value');
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
        $blueprint = $this->getMockedBlueprintFactory($config)->getBlueprint('conditional_value');
        $parameters = $blueprint->getParameters(true, true);
        $this->assertArrayHasKey('CondValue', $parameters);
        $this->assertEquals('b', $parameters['CondValue']);
    }

}