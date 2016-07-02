<?php

namespace StackFormation\Tests\Stage\ValueResolver;

use StackFormation\ValueResolver\Stage\ConditionalValue;

class ConditionalValueTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var \StackFormation\ValueResolver\Stage\ConditionalValue
     */
    protected $conditionalValueStage;

    public function setUp()
    {
        parent::setUp();

        $valueResolverMock = $this->getMock('\StackFormation\ValueResolver\ValueResolver', [], [], '', false);
        $valueResolverMock->method('resolvePlaceholders')->willReturnCallback(function($string) {
            $string = str_replace('{env:FOO}', getenv('FOO'), $string);
            $string = str_replace('{var:GlobalFoo}', 'GlobalBar', $string);
            $string = str_replace('{env:VARWITHOUTVALUE:42}', '42', $string);
            $string = str_replace('{var:BlueprintFoo}', 'BlueprintBar', $string);
            return $string;
        });

        $sourceBlueprint = $this->getMock('\StackFormation\Blueprint', [], [], '', false);

        $this->conditionalValueStage = new ConditionalValue($valueResolverMock, $sourceBlueprint, 'fooType', 'fooKey');
    }

    /**
     * @test
     */
    public function defaultIsTrue()
    {
        $this->assertTrue($this->conditionalValueStage->isTrue('default'));
    }

    /**
     * @param string $key
     * @param string $expectedValue
     * @param string|null $putenv
     * @throws \Exception
     * @test
     * @dataProvider isConditionDataProvider
     */
    public function checkKey($key, $expectedValue, $putenv = null)
    {
        $blueprint = $this->getMock('\StackFormation\Blueprint', [], [], '', false);
        $blueprint->method('getVars')->willReturn(['BlueprintFoo' => 'BlueprintBar']);
        if ($putenv) { putenv($putenv); }
        $actualValue = $this->conditionalValueStage->isTrue($key, $blueprint);
        $this->assertEquals($expectedValue, $actualValue);
    }

    /**
     * @return array
     */
    public function isConditionDataProvider()
    {
        $values = [
            ['1==1', true],
            ['1 ==1', true],
            ['1== 1', true],
            [' 1== 1', true],
            ['==', true],
            ['0==1', false],
            ['a==b', false],
            ['a==', false],
            ['{var:GlobalFoo}==GlobalBar', true],
            ['{var:GlobalFoo}=={var:GlobalFoo}', true],
            ['{env:FOO}==42', true, 'FOO=42'],
            ['{env:VARWITHOUTVALUE:42}==42', true],
            ['{env:VARWITHOUTVALUE:42}==41', false],
            ['42=={env:FOO}', true, 'FOO=42'],
            ['{env:FOO}==43', false, 'FOO=42'],
            ['43=={env:FOO}', false, 'FOO=42'],
            ['{env:FOO}=={var:GlobalFoo}', true, 'FOO=GlobalBar'],
            ['GlobalBar=={var:{env:FOO}}', true, 'FOO=GlobalFoo'],
            ['{var:BlueprintFoo}==BlueprintBar', true],
            ['prod~=/^prod$/', true],
            ['prod~=/^(prod|qa)$/', true],
            ['prd~=/^(prod|qa)$/', false],
            ['test1~=/^test.$/', true],
        ];
        $invertedValues = [];
        foreach ($values as $value) {
            if (strpos($value[0], '==') !== false) {
                $value[0] = str_replace('==', '!=', $value[0]);
                $value[1] = !$value[1];
                $invertedValues[] = $value;
            }
        }
        return array_merge($values, $invertedValues);
    }

    /**
     * @param string $key
     * @throws \Exception
     * @test
     * @dataProvider invalidConditionProvider
     */
    public function invalidCondition($key)
    {
        $this->setExpectedException('Exception', 'Invalid condition');
        $this->conditionalValueStage->isTrue($key);
    }

    /**
     * @return array
     */
    public function invalidConditionProvider()
    {
        return [
            ['foo'],
            ['foo=bar'],
        ];
    }

    /**
     * @param array $conditions
     * @param string $expectedValue
     * @param string|null $putenv
     * @test
     * @dataProvider resolveDataProvider
     */
    public function resolve(array $conditions, $expectedValue, $putenv = null)
    {
        if ($putenv) { putenv($putenv); }
        $blueprint = $this->getMock('\StackFormation\Blueprint', [], [], '', false);
        $blueprint->method('getVars')->willReturn(['BlueprintFoo' => 'BlueprintBar']);
        $actualValue = $this->conditionalValueStage->__invoke($conditions);
        $this->assertEquals($expectedValue, $actualValue);
    }

    /**
     * @return array
     */
    public function resolveDataProvider()
    {
        return [
            [['default' => 42], 42],
            [['default' => '{env:FOO}'], '{env:FOO}', 'FOO=lala'],
            [['default' => '{var:GlobalFoo}'], '{var:GlobalFoo}'],
            [['default' => '{env:FOO}{var:GlobalFoo}'], '{env:FOO}{var:GlobalFoo}', 'FOO=lala'],
            [['1==0' => 41, 'default' => 42], 42],
            [['1==0' => 41, 'default' => '{env:FOO}'], '{env:FOO}', 'FOO=lala'],
            [['1==0' => 41, 'default' => '{var:GlobalFoo}'], '{var:GlobalFoo}'],
            [['1==0' => '{env:FOO}{var:GlobalFoo}'], '', 'FOO=lala'],
            [['1==1' => '{env:FOO}'], '{env:FOO}', 'FOO=lala'],
            [['1==1' => '{var:GlobalFoo}'], '{var:GlobalFoo}'],
            [['1==1' => '{env:FOO}{var:GlobalFoo}'], '{env:FOO}{var:GlobalFoo}', 'FOO=lala'],
            [['default' => 42, '1==0' => 41], 42],
            [['default' => 42, '1==1' => 41], 42],
            [['1==2' => 42, '1==0' => 41], ''], // nothing matched
            [['{env:FOO}==prod' => 41, 'default' => 42], 41, 'FOO=prod'],
            [['{env:FOO}==prod' => 41, 'default' => 42], 42, 'FOO=stage'],
            [['{env:FOO}==prod' => 41, '{env:FOO}==stage' => 40, 'default' => 42], 40, 'FOO=stage'],
            [['{env:FOO}=={var:GlobalFoo}' => 41, '{env:FOO}==stage' => 40, 'default' => 42], 41, 'FOO=GlobalBar'],
            [['{env:FOO}=={var:BlueprintFoo}' => 41, '{env:FOO}==stage' => 40, 'default' => 42], 41, 'FOO=BlueprintBar'],
        ];
    }


}

