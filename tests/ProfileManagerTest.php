<?php

class ProfileManagerTest extends PHPUnit_Framework_TestCase {

    protected $originalCwd;

    public function setUp()
    {
        parent::setUp();
        $this->originalCwd = getcwd();
    }

    public function tearDown()
    {
        parent::tearDown();
        chdir($this->originalCwd);
    }

    public function testListProfiles()
    {
        chdir(FIXTURE_ROOT.'ProfileManager/fixture_basic');
        $profileManager = new \AwsInspector\ProfileManager();
        $this->assertEquals(
            ["test-personal", "prod-personal", "test-personal-assume"],
            $profileManager->listAllProfiles()
        );
        $this->assertEquals(
            ['profiles.yml'],
            $profileManager->getLoadedFiles()
        );
    }

    public function testInvalidProfile()
    {
        chdir(FIXTURE_ROOT . 'ProfileManager/fixture_basic');
        $profileManager = new \AwsInspector\ProfileManager();
        $this->assertFalse($profileManager->isValidProfile('invalidProfile'));
    }

    public function testLoadProfile()
    {
        chdir(FIXTURE_ROOT . 'ProfileManager/fixture_basic');
        $profileManager = new \AwsInspector\ProfileManager();
        $profileManager->loadProfile('test-personal');

        $this->assertEquals('test-personal', getenv('AWSINSPECTOR_PROFILE'));
        $this->assertEquals('us-east-1', getenv('AWS_DEFAULT_REGION'));
        $this->assertEquals('TESTACCESSKEY1', getenv('AWS_ACCESS_KEY_ID'));
        $this->assertEquals('TESTSECRETKEY1', getenv('AWS_SECRET_ACCESS_KEY'));
    }


}