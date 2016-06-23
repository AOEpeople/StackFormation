<?php

class ProfileManagerTest extends PHPUnit_Framework_TestCase {

    CONST VAULT_MAC_KEY = 'ziBH2sJjat30mpnJtJwvlp7a4G6u20aKyJ6LonVbZKs=';
    CONST VAULT_ENCRYPTION_KEY = 'XXuso82dkjakWHNGtEqUF1eB1h4nKkmZ0Cxv7aQC8Jo=';

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
            ['test1', 'test2', 'test3'],
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
        $profileManager->loadProfile('test1');

        $this->assertEquals('test1', getenv('AWSINSPECTOR_PROFILE'));
        $this->assertEquals('us-east-1', getenv('AWS_DEFAULT_REGION'));
        $this->assertEquals('TESTACCESSKEY1', getenv('AWS_ACCESS_KEY_ID'));
        $this->assertEquals('TESTSECRETKEY1', getenv('AWS_SECRET_ACCESS_KEY'));
    }

    public function testLoadMultipleFiles()
    {
        chdir(FIXTURE_ROOT . 'ProfileManager/fixture_merge_profiles');
        $profileManager = new \AwsInspector\ProfileManager();
        $this->assertEquals(
            ['test1', 'test2', 'test3', 'test1-personal', 'test2-personal'],
            $profileManager->listAllProfiles()
        );
        $this->assertEquals(
            ['profiles.yml', 'profiles.personal.yml'],
            $profileManager->getLoadedFiles()
        );
    }

    public function testEncryptedFileWithoutVaultVars() {
        if (!class_exists('\Vault\Vault')) {
            $this->markTestSkipped('aoepeople/vault must be installed to run this test');
        }
        $this->setExpectedException('Exception', 'Error decrypting profiles.yml.encrypted');
        putenv("VAULT_MAC_KEY=");
        putenv("VAULT_ENCRYPTION_KEY=");
        chdir(FIXTURE_ROOT . 'ProfileManager/fixture_encrpyted');
        $profileManager = new \AwsInspector\ProfileManager();
        $profileManager->listAllProfiles();
    }

    public function testEncryptedFileWithWrongVaultVars() {
        if (!class_exists('\Vault\Vault')) {
            $this->markTestSkipped('aoepeople/vault must be installed to run this test');
        }
        $this->setExpectedException('Exception', 'Error decrypting profiles.yml.encrypted');
        putenv("VAULT_MAC_KEY=sadsad");
        putenv("VAULT_ENCRYPTION_KEY=asdsad");
        chdir(FIXTURE_ROOT . 'ProfileManager/fixture_encrpyted');
        $profileManager = new \AwsInspector\ProfileManager();
        $profileManager->listAllProfiles();
    }

    public function testEncryptedFile() {
        if (!class_exists('\Vault\Vault')) {
            $this->markTestSkipped('aoepeople/vault must be installed to run this test');
        }
        putenv("VAULT_MAC_KEY=".self::VAULT_MAC_KEY);
        putenv("VAULT_ENCRYPTION_KEY=".self::VAULT_ENCRYPTION_KEY);
        chdir(FIXTURE_ROOT . 'ProfileManager/fixture_encrpyted');
        $profileManager = new \AwsInspector\ProfileManager();
        $this->assertEquals(
            ['test1', 'test2', 'test3'],
            $profileManager->listAllProfiles()
        );
        $this->assertEquals(
            ['profiles.yml'],
            $profileManager->getLoadedFiles()
        );
    }

    public function testEncryptedAndUnencryptedFile() {
        if (!class_exists('\Vault\Vault')) {
            $this->markTestSkipped('aoepeople/vault must be installed to run this test');
        }
        putenv("VAULT_MAC_KEY=".self::VAULT_MAC_KEY);
        putenv("VAULT_ENCRYPTION_KEY=".self::VAULT_ENCRYPTION_KEY);
        chdir(FIXTURE_ROOT . 'ProfileManager/fixture_encrpyted_mix');
        $profileManager = new \AwsInspector\ProfileManager();
        $this->assertEquals(
            ['test1', 'test2', 'test3', 'test1-personal', 'test2-personal'],
            $profileManager->listAllProfiles()
        );
        $this->assertEquals(
            ['profiles.yml', 'profiles.personal.yml'],
            $profileManager->getLoadedFiles()
        );
    }


}