<?php

namespace StackFormation\Tests;

class ValueResolverTest extends MockFacade
{
    /**
     * @var \StackFormation\ValueResolver\ValueResolver
     */
    protected $valueResolver;

    public function setUp()
    {
        $this->valueResolver = $this->getMockedPlaceholderResolver();
        parent::setUp();
    }

    public function getMockedPlaceholderResolver()
    {
        $config = $this->getMock('\StackFormation\Config', [], [], '', false);
        $config->method('getGlobalVars')->willReturn([
            'GlobalFoo' => 'GlobalBar',
            'GlobalFoo2' => 'GlobalBar2',
            'GlobalBar' => 'GlobalFoo3',
            'rescursiveA' => '{var:rescursiveB}',
            'rescursiveB' => 'Hello',
            'circularA' => '{var:circularB}',
            'circularB' => '{var:circularA}',
            'directCircular' => '{var:directCircular}',
            'Dirty' => '1.2.3'
        ]);

        $stackFactoryMock = $this->getMock('\StackFormation\StackFactory', [], [], '', false);
        $stackFactoryMock->method('getStackOutput')->willReturnCallback(function($stack) {
            if ($stack == 'stackthatdoesnotexist') { throw new \StackFormation\Exception\StackNotFoundException('stackthatdoesnotexist'); };
            return 'dummyOutput';
        });
        $stackFactoryMock->method('getStackResource')->willReturnCallback(function($stack) {
            if ($stack == 'stackthatdoesnotexist') { throw new \StackFormation\Exception\StackNotFoundException('stackthatdoesnotexist'); };
            return 'dummyResource';
        });
        $stackFactoryMock->method('getStackParameter')->willReturnCallback(function($stack) {
            if ($stack == 'stackthatdoesnotexist') { throw new \StackFormation\Exception\StackNotFoundException('stackthatdoesnotexist'); };
            return 'dummyParameter';
        });

        $profileManagerMock = $this->getMock('\StackFormation\Profile\Manager', [], [], '', false);
        $profileManagerMock->method('getStackFactory')->willReturn($stackFactoryMock);

        return new \StackFormation\ValueResolver\ValueResolver(null, $profileManagerMock, $config);
    }

    /**
     * @test
     */
    public function missingEnv()
    {
        // $this->setExpectedException('Exception', "Environment variable 'DDD' not found");
        $this->setExpectedException('Exception', "Error resolving value");
        $this->valueResolver->resolvePlaceholders(['{env:DDD}' => 13]);
    }

    /**
     * @test
     */
    public function missingVar()
    {
        // $this->setExpectedException('Exception', "Variable 'DDD' not found (Type:conditional_value, Key:{var:DDD})");
        $this->setExpectedException('Exception', "Error resolving value");
        $this->valueResolver->resolvePlaceholders(['{var:DDD}' => 13]);
    }

    /**
     * @test
     */
    public function nestedVars()
    {
        $result = $this->valueResolver->resolvePlaceholders('{var:{var:GlobalFoo}}');
        $this->assertEquals('GlobalFoo3', $result);
    }

    /**
     * @test
     */
    public function recursiveReferences()
    {
        $result = $this->valueResolver->resolvePlaceholders('{var:rescursiveA}');
        $this->assertEquals('Hello', $result);
    }

    /**
     * @test
     */
    public function directCircularReferences()
    {
        // $this->setExpectedException('Exception', 'Direct circular reference detected');
        $this->setExpectedException('Exception', 'Error resolving value');
        $this->valueResolver->resolvePlaceholders('{var:directCircular}');
    }

    /**
     * @test
     */
    public function circularReferences()
    {
        $this->setExpectedException('Exception', 'Max nesting level reached. Looks like a circular dependency.');
        $this->valueResolver->resolvePlaceholders('{var:circularA}');
    }

    /**
     * @param string $dirtyValue
     * @param string $expectedCleanValue
     * @throws \Exception
     * @test
     * @dataProvider dirtyValueProvider
     */
    public function resolveClean($dirtyValue, $expectedCleanValue)
    {
        $actualCleanValue = $this->valueResolver->resolvePlaceholders('{clean:'.$dirtyValue.'}');
        $this->assertEquals($expectedCleanValue, $actualCleanValue);
    }

    /**
     * @return array
     */
    public function dirtyValueProvider()
    {
        return [
            ['1.2.3', '123'],
            ['123', '123'],
            ['-123', '-123'],
            ['1 2 3', '123'],
            ['abc123', 'abc123'],
            [' ', ''],
            ['{var:Dirty}', '123'],
        ];
    }

    /**
     * @test
     */
    public function testDependencyTracker()
    {
        $this->assertInstanceOf('\StackFormation\DependencyTracker', $this->valueResolver->getDependencyTracker());
    }

    /**
     * @param string $value
     * @throws \Exception
     * @test
     * @dataProvider testStackNotFoundProvider
     */
    public function testStackNotFound($value)
    {
        // $this->setExpectedException('Exception', "Error resolving '$value'");
        $this->setExpectedException('Exception', "Error resolving value");
        $this->valueResolver->resolvePlaceholders($value);
    }

    /**
     * @return array
     */
    public function testStackNotFoundProvider()
    {
        return [
            ['{output:stackthatdoesnotexist:any}'],
            ['{parameter:stackthatdoesnotexist:any}'],
            ['{resource:stackthatdoesnotexist:any}'],
        ];
    }

    /**
     * @test
     */
    public function resolveMd5()
    {
        $file = FIXTURE_ROOT . 'resolve_md5.txt';
        $actualValue = $this->valueResolver->resolvePlaceholders('{md5:'.$file.'}');
        $this->assertEquals('e2fe08e5c455ef195f806dec2b7b6875', $actualValue);
    }
}
