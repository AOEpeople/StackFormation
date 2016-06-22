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
        $config = new \StackFormation\Config([FIXTURE_ROOT.'Config/blueprint.1.yml']);
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
        $config = new \StackFormation\Config([FIXTURE_ROOT.'Config/blueprint.conditional_value.yml']);
        $blueprint = $this->getMockedBlueprintFactory($config)->getBlueprint('conditional_value');
        $parameters = $blueprint->getParameters(true);
        $parameters = \StackFormation\Helper::flatten($parameters, 'ParameterKey', 'ParameterValue');
        $this->assertArrayHasKey('CondValue', $parameters);
        $this->assertEquals('c', $parameters['CondValue']);
    }

    /**
     * @test
     */
    public function getConditionalParameterValueEnvFooVal1()
    {
        putenv('Foo=Val1');
        $config = new \StackFormation\Config([FIXTURE_ROOT.'Config/blueprint.conditional_value.yml']);
        $blueprint = $this->getMockedBlueprintFactory($config)->getBlueprint('conditional_value');
        $parameters = $blueprint->getParameters(true);
        $parameters = \StackFormation\Helper::flatten($parameters, 'ParameterKey', 'ParameterValue');
        $this->assertArrayHasKey('CondValue', $parameters);
        $this->assertEquals('a', $parameters['CondValue']);
    }

    /**
     * @test
     */
    public function getConditionalParameterValueEnvFooVal2()
    {
        putenv('Foo=Val2');
        $config = new \StackFormation\Config([FIXTURE_ROOT.'Config/blueprint.conditional_value.yml']);
        $blueprint = $this->getMockedBlueprintFactory($config)->getBlueprint('conditional_value');
        $parameters = $blueprint->getParameters(true);
        $parameters = \StackFormation\Helper::flatten($parameters, 'ParameterKey', 'ParameterValue');
        $this->assertArrayHasKey('CondValue', $parameters);
        $this->assertEquals('b', $parameters['CondValue']);
    }

    /**
     * @test
     */
    public function getFlattenedTags()
    {
        $config = new \StackFormation\Config([FIXTURE_ROOT.'Config/blueprint.1.yml']);
        $blueprintTags = $this->getMockedBlueprintFactory($config)->getBlueprint('fixture1')->getTags(true);
        $blueprintTags = \StackFormation\Helper::flatten($blueprintTags);
        $this->assertArrayHasKey('TagFoo', $blueprintTags);
        $this->assertEquals('TagBar', $blueprintTags['TagFoo']);
    }

    /**
     * @test
     */
    public function getUnflattenedTags()
    {
        $config = new \StackFormation\Config([FIXTURE_ROOT.'Config/blueprint.1.yml']);
        $blueprintTags = $this->getMockedBlueprintFactory($config)->getBlueprint('fixture1')->getTags(true);
        $this->assertEquals([['Key' => 'TagFoo', 'Value' => 'TagBar']], $blueprintTags);
    }

    /**
     * @test
     */
    public function getTagsWithResolvedPlaceholder()
    {
        $value = 'Value_'.time();
        putenv('Foo='.$value);
        $config = new \StackFormation\Config([FIXTURE_ROOT.'Config/blueprint.1.yml']);
        $blueprintTags = $this->getMockedBlueprintFactory($config)->getBlueprint('fixture3')->getTags(true);
        $blueprintTags = \StackFormation\Helper::flatten($blueprintTags);
        $this->assertEquals($value, $blueprintTags['TagFoo']);
    }

    /**
     * @test
     */
    public function getTagsWithUnresolvedPlaceholder()
    {
        $value = 'Value_'.time();
        putenv('Foo='.$value);
        $config = new \StackFormation\Config([FIXTURE_ROOT.'Config/blueprint.1.yml']);
        $blueprintTags = $this->getMockedBlueprintFactory($config)->getBlueprint('fixture3')->getTags(false);
        $blueprintTags = \StackFormation\Helper::flatten($blueprintTags);
        $this->assertEquals('{env:Foo}', $blueprintTags['TagFoo']);
    }

    /**
     * @test
     */
    public function getStackname()
    {
        $config = new \StackFormation\Config([FIXTURE_ROOT.'Config/blueprint.1.yml']);
        $blueprint = $this->getMockedBlueprintFactory($config)->getBlueprint('fixture1');
        $this->assertEquals('fixture1', $blueprint->getStackName());
    }

    /**
     * @test
     */
    public function getSingleCapibility()
    {
        $config = new \StackFormation\Config([FIXTURE_ROOT.'Config/blueprint.1.yml']);
        $blueprint = $this->getMockedBlueprintFactory($config)->getBlueprint('fixture4');
        $this->assertEquals(['FOO'], $blueprint->getCapabilities());
    }

    /**
     * @test
     */
    public function getMulitpleCapibilities()
    {
        $config = new \StackFormation\Config([FIXTURE_ROOT.'Config/blueprint.1.yml']);
        $blueprint = $this->getMockedBlueprintFactory($config)->getBlueprint('fixture5');
        $this->assertEquals(['FOO', 'BAR'], $blueprint->getCapabilities());
    }

    /**
     * @test
     */
    public function getBasepath()
    {
        $config = new \StackFormation\Config([FIXTURE_ROOT.'Config/blueprint.1.yml']);
        $blueprint = $this->getMockedBlueprintFactory($config)->getBlueprint('fixture1');
        $this->assertEquals(FIXTURE_ROOT.'Config', $blueprint->getBasePath());
    }

    /**
     * @test
     */
    public function runBeforeScriptsWith()
    {
        $testfile = tempnam(sys_get_temp_dir(), __METHOD__);
        putenv("TESTFILE=$testfile");
        $config = new \StackFormation\Config([FIXTURE_ROOT.'Config/blueprint.1.yml']);
        $blueprint = $this->getMockedBlueprintFactory($config)->getBlueprint('fixture6');
        $blueprint->executeBeforeScripts();

        $this->assertStringEqualsFile($testfile, 'HELLO WORLD');
        unlink($testfile);
    }

    /**
     * @test
     */
    public function loadStackPolicy()
    {
        $config = new \StackFormation\Config([FIXTURE_ROOT.'Config/blueprint.1.yml']);
        $blueprint = $this->getMockedBlueprintFactory($config)->getBlueprint('fixture7');

        $this->assertContains('"Action" : "Update:Delete"', $blueprint->getStackPolicy());
    }


}