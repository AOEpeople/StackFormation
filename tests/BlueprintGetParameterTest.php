<?php

class BlueprintGetParameterTest extends PHPUnit_Framework_TestCase {

    protected function getMockedBlueprint($blueprintConfig, $name=null)
    {
        $configMock = $this->getMock('\StackFormation\Config', [], [], '', false);

        $stackFactoryMock = $this->getMock('\StackFormation\StackFactory', [], [], '', false);
        $stackFactoryMock->method('getStackOutput')->willReturn('dummyOutput');
        $stackFactoryMock->method('getStackResource')->willReturn('dummyResource');
        $stackFactoryMock->method('getStackParameter')->willReturn('dummyParameter');

        $placeholderResolver = new \StackFormation\PlaceholderResolver(
            new \StackFormation\DependencyTracker(),
            $stackFactoryMock,
            $configMock
        );

        $conditionalValueResolver = new \StackFormation\ConditionalValueResolver($placeholderResolver);

        if (is_null($name)) {
            $name = 'blueprint_mock_'.time();
        }
        return new \StackFormation\Blueprint($name, $blueprintConfig, $placeholderResolver, $conditionalValueResolver);
    }

    /**
     * @test
     * @dataProvider getParameterProvider
     */
    public function getParameter($rawParameterValue, $expectedResolvedValue, $putenv=null)
    {
        if ($putenv) {
            putenv($putenv);
        }
        $blueprint = $this->getMockedBlueprint([ 'parameters' => [
            'Foo' => $rawParameterValue
        ]]);
        $parameters = $blueprint->getParameters(true, true);
        $this->assertEquals($expectedResolvedValue, $parameters['Foo']);
    }

    public function getParameterProvider() {
        return [
            ['Foo', 'Foo'],
            [' Foo ', ' Foo '],
            ['{env:ENVIRONMENT}', 'prod', 'ENVIRONMENT=prod'],
            ['prefix_{env:ENVIRONMENT}', 'prefix_prod', 'ENVIRONMENT=prod'],
            ['{env:ENVIRONMENT}_suffix', 'prod_suffix', 'ENVIRONMENT=prod'],
            ['prefix_{env:ENVIRONMENT}_suffix', 'prefix_prod_suffix', 'ENVIRONMENT=prod'],
            ['{env:ENVIRONMENT}{env:ENVIRONMENT}', 'prodprod', 'ENVIRONMENT=prod'],
            ['{resource:stack:key}', 'dummyResource'],
            ['{output:stack:key}', 'dummyOutput'],
            ['{parameter:stack:key}', 'dummyParameter'],
        ];
    }

    /**
     * @param string $invalidKey
     * @test
     * @dataProvider invalidKeyProvider
     */
    public function invalidParameterKey($invalidKey)
    {
        $this->setExpectedException('Exception', "Invalid parameter key '$invalidKey'.");
        $blueprint = $this->getMockedBlueprint([ 'parameters' => [
            $invalidKey => 'asdsad'
        ]]);
        $blueprint->getParameters(true, true);
    }

    public function invalidKeyProvider()
    {
        return [
            //[true],
            //[false],
            //[["Hello World"]],
            //[new stdClass()],
            [',sd'],
            ['"sdsd"'],
            ['Invalid=Key'],
            [str_repeat('A', 256)],
        ];
    }



    /**
     * @test
     */
    public function getUnsresolvedParameter()
    {
        $blueprint = $this->getMockedBlueprint([ 'parameters' => [
            'Foo' => '{env:DONTRESOVLE}'
        ]]);
        $parameters = $blueprint->getParameters(false, true);
        $this->assertEquals('{env:DONTRESOVLE}', $parameters['Foo']);
    }


    /**
     * @test
     */
    public function basePathNotSet()
    {
        $this->setExpectedException('Exception', "No basepath set");
        $blueprint = $this->getMockedBlueprint([]);
        $basePath = $blueprint->getBasePath();
    }


    /**
     * @test
     */
    public function invalidBasePath()
    {
        $this->setExpectedException('Exception', "Invalid basepath '/does/not/exist'");
        $blueprint = $this->getMockedBlueprint(['basepath' => '/does/not/exist']);
        $basePath = $blueprint->getBasePath();
    }


    /**
     * @test
     */
    public function getBasePath()
    {
        $blueprint = $this->getMockedBlueprint(['basepath' => FIXTURE_ROOT.'Config']);
        $basePath = $blueprint->getBasePath();
        $this->assertEquals(FIXTURE_ROOT.'Config', $basePath);
    }


}