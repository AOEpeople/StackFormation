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

    /**
     * @test
     */
    public function getFlattenedTags()
    {
        $config = new \StackFormation\Config([FIXTURE_ROOT.'/Config/blueprint.1.yml']);
        $blueprintTags = $this->getMockedBlueprintFactory($config)->getBlueprint('fixture1')->getTags(true, true);
        $this->assertArrayHasKey('TagFoo', $blueprintTags);
        $this->assertEquals('TagBar', $blueprintTags['TagFoo']);
    }

    /**
     * @test
     */
    public function getUnflattenedTags()
    {
        $config = new \StackFormation\Config([FIXTURE_ROOT.'/Config/blueprint.1.yml']);
        $blueprintTags = $this->getMockedBlueprintFactory($config)->getBlueprint('fixture1')->getTags(true, false);
        $this->assertEquals([['Key' => 'TagFoo', 'Value' => 'TagBar']], $blueprintTags);
    }

    /**
     * @test
     */
    public function getTagsWithResolvedPlaceholder()
    {
        $value = 'Value_'.time();
        putenv('Foo='.$value);
        $config = new \StackFormation\Config([FIXTURE_ROOT.'/Config/blueprint.1.yml']);
        $blueprintTags = $this->getMockedBlueprintFactory($config)->getBlueprint('fixture3')->getTags(true, true);
        $this->assertEquals($value, $blueprintTags['TagFoo']);
    }

    /**
     * @test
     */
    public function getTagsWithUnresolvedPlaceholder()
    {
        $value = 'Value_'.time();
        putenv('Foo='.$value);
        $config = new \StackFormation\Config([FIXTURE_ROOT.'/Config/blueprint.1.yml']);
        $blueprintTags = $this->getMockedBlueprintFactory($config)->getBlueprint('fixture3')->getTags(false, true);
        $this->assertEquals('{env:Foo}', $blueprintTags['TagFoo']);
    }

}