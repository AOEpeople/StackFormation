<?php

class PreprocessorTest extends PHPUnit_Framework_TestCase {

    /**
     * @var \StackFormation\Preprocessor
     */
    protected $preprocessor;

    public function setUp()
    {
        parent::setUp();
        $this->preprocessor = new \StackFormation\Preprocessor();
    }
    
    public function 

    /**
     * @throws Exception
     * @test
     * @dataProvider stripCommentsDataProvider
     */
    public function stripComments($inputString, $expectedStrippedString) {
        $actualStrippedString = $this->preprocessor->stripComments($inputString);
        $this->assertEquals($expectedStrippedString, $actualStrippedString);
    }

    public function stripCommentsDataProvider() {
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
            ["Hello World /* Multiline\nComment */ Hello World", 'Hello World  Hello World']
        ];
    }

    /**
     * @test
     */
    public function parseRefs() {
        $inputString = '"Key": "Name", "Value": "magento-{Ref:Environment}-{Ref:Build}-instance"';
        $expectedString =  '"Key": "Name", "Value": {"Fn::Join": ["", ["magento-", {"Ref":"Environment"}, "-", {"Ref":"Build"}, "-instance"]]}';
        $actualString = $this->preprocessor->parseRefs($inputString);
        $this->assertEquals($expectedString, $actualString);
    }

    /**
     * @test
     */
    public function expandPort() {
        $inputString = '{"IpProtocol": "tcp", "Port": "80", "CidrIp": "1.2.3.4/32"},';
        $expectedString = '{"IpProtocol": "tcp", "FromPort": "80", "ToPort": "80", "CidrIp": "1.2.3.4/32"},';
        $actualString = $this->preprocessor->expandPort($inputString);
        $this->assertEquals($expectedString, $actualString);
    }

    /**
     * @test
     * @dataProvider injectFileContentFnFileContentFixtureProvider
     */
    public function injectFileContentFnFileContent($fixtureDirectory) {
        $templatePath = FIXTURE_ROOT. $fixtureDirectory . '/blueprint/input.template';
        $inputString = file_get_contents($templatePath);
        $expectedString = file_get_contents(FIXTURE_ROOT. $fixtureDirectory. '/blueprint/expected.template');
        $actualString = $this->preprocessor->injectFilecontent($inputString, dirname($templatePath));
        $this->assertEquals($expectedString, $actualString);
    }

    public function injectFileContentFnFileContentFixtureProvider() {
        return [
            ['injectFileContentFnFileContentFromSameDirectory'],
            ['injectFileContentFnFileContentFromSubDirectory'],
            ['injectFileContentFnFileContentFromParentDirectory']
        ];
    }

    /**
     * @test
     */
    public function injectFileContentFnFileContentThrowExpectionIfFileDoesNotExist() {
        $this->setExpectedExceptionRegExp('Exception', '/^File (.+) not found$/');
        $fixtureDirectory = 'injectFileContentFnFileContentExceptionOnFileNotFound';
        $templatePath = FIXTURE_ROOT. $fixtureDirectory . '/blueprint/input.template';
        $inputString = file_get_contents($templatePath);
        $this->preprocessor->injectFilecontent($inputString, dirname($templatePath));
    }



}