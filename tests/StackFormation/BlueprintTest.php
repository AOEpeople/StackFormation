<?php

namespace StackFormation\Tests;

class BlueprintTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param \StackFormation\Config $config
     * @return \StackFormation\BlueprintFactory
     */
    protected function getMockedBlueprintFactory(\StackFormation\Config $config)
    {
        $stackFactoryMock = $this->getMock('\StackFormation\StackFactory', [], [], '', false);
        $stackFactoryMock->method('getStackOutput')->willReturn('dummyOutput');
        $stackFactoryMock->method('getStackResource')->willReturn('dummyResource');
        $stackFactoryMock->method('getStackParameter')->willReturn('dummyParameter');

        $profileManagerMock = $this->getMock('\StackFormation\Profile\Manager', [], [], '', false);
        $profileManagerMock->method('getStackFactory')->willReturn($stackFactoryMock);

        $valueResolver = new \StackFormation\ValueResolver(null, $profileManagerMock, $config);

        return new \StackFormation\BlueprintFactory($config, $valueResolver);
    }

    /**
     * @test
     */
    public function getVariable()
    {
        $config = new \StackFormation\Config([FIXTURE_ROOT . 'Config/blueprint.1.yml']);
        $blueprintVars = $this->getMockedBlueprintFactory($config)->getBlueprint('fixture1')->getVars();
        $this->assertArrayHasKey('BlueprintFoo', $blueprintVars);
        $this->assertEquals('BlueprintBar', $blueprintVars['BlueprintFoo']);
    }

    public function testBlueprintVarOverridesGlobalVar()
    {
        $this->markTestIncomplete();
    }

    /**
     * @test
     */
    public function getConditionalParameterValueDefault()
    {
        putenv('Foo=Val5');
        $config = new \StackFormation\Config([FIXTURE_ROOT . 'Config/blueprint.conditional_value.yml']);
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
        $config = new \StackFormation\Config([FIXTURE_ROOT . 'Config/blueprint.conditional_value.yml']);
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
        $config = new \StackFormation\Config([FIXTURE_ROOT . 'Config/blueprint.conditional_value.yml']);
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
        $config = new \StackFormation\Config([FIXTURE_ROOT . 'Config/blueprint.1.yml']);
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
        $config = new \StackFormation\Config([FIXTURE_ROOT . 'Config/blueprint.1.yml']);
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
        $config = new \StackFormation\Config([FIXTURE_ROOT . 'Config/blueprint.1.yml']);
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
        $config = new \StackFormation\Config([FIXTURE_ROOT . 'Config/blueprint.1.yml']);
        $blueprintTags = $this->getMockedBlueprintFactory($config)->getBlueprint('fixture3')->getTags(false);
        $blueprintTags = \StackFormation\Helper::flatten($blueprintTags);
        $this->assertEquals('{env:Foo}', $blueprintTags['TagFoo']);
    }

    /**
     * @test
     */
    public function getStackname()
    {
        $config = new \StackFormation\Config([FIXTURE_ROOT . 'Config/blueprint.1.yml']);
        $blueprint = $this->getMockedBlueprintFactory($config)->getBlueprint('fixture1');
        $this->assertEquals('fixture1', $blueprint->getStackName());
    }

    /**
     * @test
     */
    public function getSingleCapibility()
    {
        $config = new \StackFormation\Config([FIXTURE_ROOT . 'Config/blueprint.1.yml']);
        $blueprint = $this->getMockedBlueprintFactory($config)->getBlueprint('fixture4');
        $this->assertEquals(['FOO'], $blueprint->getCapabilities());
    }

    /**
     * @test
     */
    public function getMulitpleCapibilities()
    {
        $config = new \StackFormation\Config([FIXTURE_ROOT . 'Config/blueprint.1.yml']);
        $blueprint = $this->getMockedBlueprintFactory($config)->getBlueprint('fixture5');
        $this->assertEquals(['FOO', 'BAR'], $blueprint->getCapabilities());
    }

    /**
     * @test
     */
    public function getBasepath()
    {
        $config = new \StackFormation\Config([FIXTURE_ROOT . 'Config/blueprint.1.yml']);
        $blueprint = $this->getMockedBlueprintFactory($config)->getBlueprint('fixture1');
        $this->assertEquals(FIXTURE_ROOT . 'Config', $blueprint->getBasePath());
    }

    /**
     * @test
     */
    public function loadStackPolicy()
    {
        $config = new \StackFormation\Config([FIXTURE_ROOT . 'Config/blueprint.1.yml']);
        $blueprint = $this->getMockedBlueprintFactory($config)->getBlueprint('fixture7');
        $this->assertContains('"Action" : "Update:Delete"', $blueprint->getStackPolicy());
    }

    /**
     * @test
     */
    public function testGetProfile()
    {
        $config = new \StackFormation\Config([FIXTURE_ROOT . 'Config/blueprint.select_profile.yml']);
        $profile = $this->getMockedBlueprintFactory($config)->getBlueprint('fixture_selectprofile')->getProfile();
        $this->assertEquals('myprofile', $profile);
    }

    /**
     * @test
     */
    public function testselectProfile()
    {
        $config = new \StackFormation\Config([FIXTURE_ROOT . 'Config/blueprint.select_profile.yml']);
        $profile = $this->getMockedBlueprintFactory($config)->getBlueprint('fixture_selectprofile')->getProfile();
        $this->assertEquals('myprofile', $profile);
    }

    /**
     * @test
     * @dataProvider testselectProfileConditionalProvider
     */
    public function testselectProfileConditional($foo, $expectedProfile)
    {
        putenv('Foo='.$foo);
        $config = new \StackFormation\Config([FIXTURE_ROOT . 'Config/blueprint.select_profile.yml']);
        $profile = $this->getMockedBlueprintFactory($config)->getBlueprint('fixture_selectprofile_conditional')->getProfile();
        $this->assertEquals($expectedProfile, $profile);
    }

    public function testselectProfileConditionalProvider()
    {
        return [
            ['Val1', 'a'],
            ['Val2', 'b'],
            ['somethingelse', 'c'],
        ];
    }

    /**
     * @test
     * @dataProvider testConditionalGlobalProvider
     */
    public function testConditionalGlobalVar($foo, $expectedValue)
    {
        putenv('Foo='.$foo);
        $config = new \StackFormation\Config([FIXTURE_ROOT . 'Config/blueprint.conditional_vars.yml']);
        $blueprint = $this->getMockedBlueprintFactory($config)->getBlueprint('fixture_var_conditional_global');
        $parameters = $blueprint->getParameters(true);
        $parameters = \StackFormation\Helper::flatten($parameters, 'ParameterKey', 'ParameterValue');
        $this->assertEquals($expectedValue, $parameters['Parameter1']);
    }

    public function testConditionalGlobalProvider()
    {
        return [
            ['Val1', 'a'],
            ['Val2', 'b'],
            ['somethingelse', 'c'],
        ];
    }

    /**
     * @test
     * @dataProvider testConditionalGlobalProvider
     */
    public function testConditionalLocalVar($foo, $expectedValue)
    {
        putenv('Foo='.$foo);
        $config = new \StackFormation\Config([FIXTURE_ROOT . 'Config/blueprint.conditional_vars.yml']);
        $blueprint = $this->getMockedBlueprintFactory($config)->getBlueprint('fixture_var_conditional_local');
        $parameters = $blueprint->getParameters(true);
        $parameters = \StackFormation\Helper::flatten($parameters, 'ParameterKey', 'ParameterValue');
        $this->assertEquals($expectedValue, $parameters['Parameter1']);
    }

    /**
     * @test
     */
    public function testSwitchProfile()
    {
        $profileManagerMock = $this->getMock('\StackFormation\Profile\Manager', [], [], '', false);
        $profileManagerMock
            ->expects($this->exactly(2))
            ->method('getStackFactory')
            ->willReturnCallback(function($profile) {
                if ($profile == 'myprofile1') {
                    $stackFactoryMock = $this->getMock('\StackFormation\StackFactory', [], [], 'LocalStackFactory', false);
                    $stackFactoryMock->method('getStackOutput')->willReturn('dummyOutputLocal');
                    return $stackFactoryMock;
                }
                if ($profile == 'myprofile2') {
                    $subStackFactoryMock = $this->getMock('\StackFormation\StackFactory', [], [], 'RemoteStackFactory', false);
                    $subStackFactoryMock->method('getStackOutput')->willReturn('dummyOutputRemote');
                    return $subStackFactoryMock;
                }
                return null;
            });

        $config = new \StackFormation\Config([FIXTURE_ROOT . 'Config/blueprint.switch_profile.yml']);

        $valueResolver = new \StackFormation\ValueResolver(
            null,
            $profileManagerMock,
            $config
        );

        $blueprintFactory = new \StackFormation\BlueprintFactory($config, $valueResolver);

        $blueprint = $blueprintFactory->getBlueprint('switch_profile');
        $parameters = $blueprint->getParameters(true);
        $parameters = \StackFormation\Helper::flatten($parameters, 'ParameterKey', 'ParameterValue');

        $this->assertEquals('Bar1', $parameters['Foo1']);
        $this->assertEquals('dummyOutputRemote', $parameters['Foo2']);
        $this->assertEquals('dummyOutputLocal', $parameters['Foo3']);
    }

    /**
     * @test
     */
    public function testSwitchProfileComplex()
    {
        putenv('ACCOUNT=t');
        putenv('BASE_TYPE_VERSION=42');

        $profileManagerMock = $this->getMock('\StackFormation\Profile\Manager', [], [], '', false);
        $profileManagerMock
            ->method('getStackFactory')
            ->willReturnCallback(function() {
                $stackFactoryMock = $this->getMock('\StackFormation\StackFactory', ['getStackOutput'], [], '', false);
                $stackFactoryMock->method('getStackOutput')->willReturnCallback(function($stackName, $key) { return "DummyValue|$stackName|$key"; });
                return $stackFactoryMock;
            });

        $config = new \StackFormation\Config([FIXTURE_ROOT . 'Config/blueprint.switch_profile.yml']);

        $valueResolver = new \StackFormation\ValueResolver(
            null,
            $profileManagerMock,
            $config
        );

        $blueprintFactory = new \StackFormation\BlueprintFactory($config, $valueResolver);

        $blueprint = $blueprintFactory->getBlueprint('switch_profile_complex');
        $parameters = $blueprint->getParameters(true);
        $parameters = \StackFormation\Helper::flatten($parameters, 'ParameterKey', 'ParameterValue');

        $this->assertEquals('DummyValue|ecom-t-all-ami-types-42-stack|VarnishAmi', $parameters['VarnishAmi']);
    }

    /**
     * @test
     */
    public function blueprintDoesNotExist()
    {
        $this->setExpectedException('\StackFormation\Exception\BlueprintNotFoundException', "Blueprint 'doenotexist' not found.");
        $config = new \StackFormation\Config([FIXTURE_ROOT . 'Config/blueprint.1.yml']);
        $profileManagerMock = $this->getMock('\StackFormation\Profile\Manager', [], [], '', false);

        $valueResolver = new \StackFormation\ValueResolver(null, $profileManagerMock, $config);
        $blueprintFactory = new \StackFormation\BlueprintFactory($config, $valueResolver);
        $blueprintFactory->getBlueprint('doenotexist');
    }

    /**
     * @test
     */
    public function getBlueprintReference()
    {
        $config = new \StackFormation\Config([FIXTURE_ROOT . 'Config/blueprint.reference.yml']);

        putenv('FOO3=BAR3');
        putenv('FOO2=BAR2');
        putenv('FOO1=BAR1');

        $profileManagerMock = $this->getMock('\StackFormation\Profile\Manager', [], [], '', false);

        $valueResolver = new \StackFormation\ValueResolver(
            null,
            $profileManagerMock,
            $config
        );
        $blueprintFactory = new \StackFormation\BlueprintFactory($config, $valueResolver);
        $blueprint = $blueprintFactory->getBlueprint('reference-fixture-{env:FOO1}');
        $blueprint->gatherDependencies();
        $this->assertEquals('reference-fixture-BAR1', $blueprint->getStackName());
        $blueprintReference = $blueprint->getBlueprintReference();

        $this->assertEquals('reference-fixture-{env:FOO1}', $blueprintReference['Blueprint']);
        $this->assertEquals([
            'FOO1' => 'BAR1',
            'FOO2' => 'BAR2',
            'FOO3' => 'BAR3'
        ], $blueprintReference['EnvironmentVariables']);
    }

    /**
     * @test
     */
    public function getPreprocessedTemplate()
    {
        $config = new \StackFormation\Config([FIXTURE_ROOT . 'Config/blueprint.1.yml']);
        $profileManagerMock = $this->getMock('\StackFormation\Profile\Manager', [], [], '', false);
        $valueResolver = new \StackFormation\ValueResolver(null, $profileManagerMock, $config);
        $blueprintFactory = new \StackFormation\BlueprintFactory($config, $valueResolver);
        $blueprint = $blueprintFactory->getBlueprint('fixture1');
        $template = $blueprint->getPreprocessedTemplate();
        $template = json_decode($template, true);
        $this->assertArrayHasKey('Resources', $template);
        $this->assertArrayHasKey('MyResource', $template['Resources']);
        $this->assertEquals('AWS::CloudFormation::WaitConditionHandle', $template['Resources']['MyResource']['Type']);
    }

    /**
     * @test
     */
    public function getPreprocessedTemplateContainsBlueprintReference()
    {
        $config = new \StackFormation\Config([FIXTURE_ROOT . 'Config/blueprint.reference.yml']);
        $profileManagerMock = $this->getMock('\StackFormation\Profile\Manager', [], [], '', false);
        $valueResolver = new \StackFormation\ValueResolver(null, $profileManagerMock, $config);
        $blueprintFactory = new \StackFormation\BlueprintFactory($config, $valueResolver);
        $blueprint = $blueprintFactory->getBlueprint('reference-fixture-{env:FOO1}');
        $template = $blueprint->getPreprocessedTemplate();
        $template = json_decode($template, true);
        $this->assertArrayHasKey('Metadata', $template);
        $this->assertArrayHasKey('StackFormation', $template['Metadata']);
        $this->assertArrayHasKey('Blueprint', $template['Metadata']['StackFormation']);
        $this->assertEquals('reference-fixture-{env:FOO1}', $template['Metadata']['StackFormation']['Blueprint']);

        $this->assertArrayHasKey('EnvironmentVariables', $template['Metadata']['StackFormation']);
        $this->assertEquals([
            'FOO1' => 'BAR1',
            'FOO2' => 'BAR2',
            'FOO3' => 'BAR3'
        ], $template['Metadata']['StackFormation']['EnvironmentVariables']);
    }
}
