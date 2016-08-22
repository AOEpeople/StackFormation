<?php

namespace StackFormation\Tests;

class PreprocessorTest extends MockFacade
{
    /**
     * @var \StackFormation\Preprocessor
     */
    protected $preprocessor;

    public function setUp()
    {
        parent::setUp();
        $this->preprocessor = new \StackFormation\Preprocessor();
    }

    /**
     * @param string $fixtureDirectory
     * @throws \Exception
     * @test
     * @dataProvider processFileDataProvider
     */
    public function processFile($fixtureDirectory)
    {
        $prefix = FIXTURE_ROOT . 'Preprocessor/';
        $prefix .= $fixtureDirectory . '/';
        $templatePath = $prefix . 'blueprint/input.template';
        $fileContent = file_get_contents($templatePath);
        $this->assertEquals(
            $this->preprocessor->processJson($fileContent, dirname($templatePath)),
            file_get_contents($prefix. 'blueprint/expected.template')
        );
    }

    public function processFileDataProvider()
    {
        $prefix = FIXTURE_ROOT . 'Preprocessor/';
        $directories = glob($prefix.'*', GLOB_ONLYDIR);
        array_walk($directories, function(&$directory) use ($prefix) {
            $directory = [ str_replace($prefix, '', $directory)];
        });
        return $directories;
    }

    /**
     * @param string $inputJson
     * @param string $expectedJson
     * @throws \Exception
     * @test
     * @dataProvider processJsonDataProvider
     */
    public function processJson($inputJson, $expectedJson)
    {
        $this->assertEquals(
            $this->preprocessor->processJson($inputJson, sys_get_temp_dir()),
            $expectedJson
        );
    }

    /**
     * @return array
     */
    public function processJsonDataProvider()
    {
        return [
            // strip comments
            ['Hello World /* Comment */', 'Hello World '],
            ['Hello World /* Comment */ Hello World', 'Hello World  Hello World'],
            ['/* Comment */ Hello World', ' Hello World'],
            // support single quotes
            ["Hello World /* 'Comment' */ Hello World", 'Hello World  Hello World'],
            // ignore double quotes
            ['Hello World /* "Comment" */', 'Hello World /* "Comment" */'],
            ['Hello World /* "Comment */', 'Hello World /* "Comment */'],
            // multi-line
            ["Hello World /* Multiline\nComment */ Hello World", 'Hello World  Hello World'],
            // parseRefInDoubleQuotedStrings
            ['"Key": "Name", "Value": "magento-{Ref:Environment}-{Ref:Build}-instance"', '"Key": "Name", "Value": {"Fn::Join": ["", ["magento-", {"Ref":"Environment"}, "-", {"Ref":"Build"}, "-instance"]]}'],
            // expandPort
            ['{"IpProtocol": "tcp", "Port": "80", "CidrIp": "1.2.3.4/32"},', '{"IpProtocol": "tcp", "FromPort": "80", "ToPort": "80", "CidrIp": "1.2.3.4/32"},'],
            // replace ref
            ["WAIT_CONDITION_HANDLE='{Ref:WaitConditionHandle}'", "WAIT_CONDITION_HANDLE='\", {\"Ref\": \"WaitConditionHandle\"}, \"'"],
            ["REGION='{Ref:AWS::Region}'", "REGION='\", {\"Ref\": \"AWS::Region\"}, \"'"],
            ['"Aliases": { "Fn::Split": [",", "a,b,c"] }', '"Aliases": ["a", "b", "c"]'],
            ['"Aliases": { "Fn::Split": ["+", "a,b,c"] }', '"Aliases": ["a,b,c"]'],
            ['"Aliases": { "Fn::Split": ["+", "a+b+c"] }', '"Aliases": ["a", "b", "c"]'],
        ];
    }
}
