<?php

namespace StackFormation\Tests;

class ProfileManagerTest extends \PHPUnit_Framework_TestCase
{
    CONST VAULT_MAC_KEY = 'ziBH2sJjat30mpnJtJwvlp7a4G6u20aKyJ6LonVbZKs=';
    CONST VAULT_ENCRYPTION_KEY = 'XXuso82dkjakWHNGtEqUF1eB1h4nKkmZ0Cxv7aQC8Jo=';

    protected $originalCwd;

    /**
     * @var \StackFormation\Profile\Manager
     */
    protected $profileManager;

    public function setUp()
    {
        parent::setUp();
        $this->originalCwd = getcwd();
        $this->profileManager = new \StackFormation\Profile\Manager();
    }

    public function tearDown()
    {
        parent::tearDown();
        chdir($this->originalCwd);
    }

    public function testListProfiles()
    {
        chdir(FIXTURE_ROOT.'ProfileManager/fixture_basic');
        $this->assertEquals(
            ['test1', 'test2', 'test3'],
            $this->profileManager->listAllProfiles()
        );
    }

    public function testInvalidProfile()
    {
        chdir(FIXTURE_ROOT . 'ProfileManager/fixture_basic');
        $this->setExpectedException('Exception', 'Invalid profile: invalidProfile');
        $this->profileManager->getClient('CloudFormation', 'invalidProfile');
    }

    public function testLoadProfile()
    {
        chdir(FIXTURE_ROOT . 'ProfileManager/fixture_basic');

        putenv("AWS_DEFAULT_REGION=eu-west-1");
        $cfnClient = $this->profileManager->getClient('CloudFormation', 'test1');

        $credentials = $cfnClient->getCredentials()->wait(true);

        $this->assertEquals('TESTACCESSKEY1', $credentials->getAccessKeyId());
        $this->assertEquals('TESTSECRETKEY1', $credentials->getSecretKey());
    }

    public function testLoadMultipleFiles()
    {
        chdir(FIXTURE_ROOT . 'ProfileManager/fixture_merge_profiles');
        $this->assertEquals(
            ['test1', 'test2', 'test3', 'test1-personal', 'test2-personal'],
            $this->profileManager->listAllProfiles()
        );
    }

    public function testEncryptedFileWithoutVaultVars()
    {
        if (!class_exists('\Vault\Vault')) {
            $this->markTestSkipped('aoepeople/vault must be installed to run this test');
        }
        $this->setExpectedException('Exception', 'Error decrypting profiles.yml.encrypted');
        putenv("VAULT_MAC_KEY=");
        putenv("VAULT_ENCRYPTION_KEY=");
        chdir(FIXTURE_ROOT . 'ProfileManager/fixture_encrpyted');
        $this->profileManager->listAllProfiles();
    }

    public function testEncryptedFileWithWrongVaultVars()
    {
        if (!class_exists('\Vault\Vault')) {
            $this->markTestSkipped('aoepeople/vault must be installed to run this test');
        }
        $this->setExpectedException('Exception', 'Error decrypting profiles.yml.encrypted');
        putenv("VAULT_MAC_KEY=sadsad");
        putenv("VAULT_ENCRYPTION_KEY=asdsad");
        chdir(FIXTURE_ROOT . 'ProfileManager/fixture_encrpyted');
        $this->profileManager->listAllProfiles();
    }

    public function testEncryptedFile()
    {
        if (!class_exists('\Vault\Vault')) {
            $this->markTestSkipped('aoepeople/vault must be installed to run this test');
        }
        putenv("VAULT_MAC_KEY=".self::VAULT_MAC_KEY);
        putenv("VAULT_ENCRYPTION_KEY=".self::VAULT_ENCRYPTION_KEY);
        chdir(FIXTURE_ROOT . 'ProfileManager/fixture_encrpyted');
        $this->assertEquals(
            ['test1', 'test2', 'test3'],
            $this->profileManager->listAllProfiles()
        );
    }

    public function testEncryptedAndUnencryptedFile()
    {
        if (!class_exists('\Vault\Vault')) {
            $this->markTestSkipped('aoepeople/vault must be installed to run this test');
        }
        putenv("VAULT_MAC_KEY=".self::VAULT_MAC_KEY);
        putenv("VAULT_ENCRYPTION_KEY=".self::VAULT_ENCRYPTION_KEY);
        chdir(FIXTURE_ROOT . 'ProfileManager/fixture_encrpyted_mix');
        $this->assertEquals(
            ['test1', 'test2', 'test3', 'test1-personal', 'test2-personal'],
            $this->profileManager->listAllProfiles()
        );
    }
}
