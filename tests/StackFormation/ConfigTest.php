<?php

namespace StackFormation\Tests;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function missingTemplate()
    {
        $this->setExpectedException('Exception', "Stackname 'a' does not specify a template.");
        $config = new \StackFormation\Config([FIXTURE_ROOT . '/Config/blueprint.template_missing.yml']);
    }

    /**
     * @test
     */
    public function missingTemplateFile()
    {
        $this->setExpectedException('Exception', "Could not find template file doesnotexist.template referenced in stack a");
        $config = new \StackFormation\Config([FIXTURE_ROOT . '/Config/blueprint.templatefile_missing.yml']);
    }

    /**
     * @test
     */
    public function duplicateStackName()
    {
        $this->setExpectedException('Exception', "Stackname 'a' was declared more than once.");
        $config = new \StackFormation\Config([FIXTURE_ROOT . '/Config/blueprint.duplicate_stackname.yml']);
    }

    /**
     * @test
     */
    public function globalVariable()
    {
        $config = new \StackFormation\Config([FIXTURE_ROOT . '/Config/blueprint.1.yml']);
        $globalVars = $config->getGlobalVars();
        $this->assertArrayHasKey('GlobalFoo', $globalVars);
        $this->assertEquals('GlobalBar', $globalVars['GlobalFoo']);
    }

    /**
     * @test
     */
    public function getBlueprints()
    {
        $config = new \StackFormation\Config([FIXTURE_ROOT . '/Config/blueprint.1.yml']);
        $this->assertTrue($config->blueprintExists('fixture1'));
        $this->assertTrue($config->blueprintExists('fixture2'));
        $names = $config->getBlueprintNames();
        $this->assertTrue(in_array('fixture1', $names));
        $this->assertTrue(in_array('fixture2', $names));
    }

    /**
     * @test
     */
    public function invalidAttribute()
    {
        $this->setExpectedException(
            '\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException',
            'Unrecognized option "parameter" under "root.blueprints.invalid_attribute"'
        );
        $config = new \StackFormation\Config([FIXTURE_ROOT . '/Config/blueprint.invalid_attribute.yml']);
    }

    //public function testDuplicateGlobalVar()
    //{
    //    $this->markTestSkipped('This is not so trivial :)');
    //    $this->setExpectedException('Exception');
    //    $config = new \StackFormation\Config([FIXTURE_ROOT . '/Config/blueprint.duplicateglobalvar.yml']);
    //    $vars = $config->getGlobalVars();
    //}
}
