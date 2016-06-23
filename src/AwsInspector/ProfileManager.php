<?php

namespace AwsInspector;

class ProfileManager {

    protected $config;
    protected $loadedFiles = [];

    public function listAllProfiles() {
        return array_keys($this->getConfig());
    }

    public function getProfileConfig($profile) {
        if (!$this->isValidProfile($profile)) {
            throw new \InvalidArgumentException('Invalid profile ' . $profile);
        }
        $config = $this->getConfig();
        return $config[$profile];
    }

    public function isValidProfile($profile) {
        $config = $this->getConfig();
        return isset($config[$profile]);
    }

    public function loadProfile($profile) {
        foreach ($this->getEnvVars($profile) as $envVar) {
            putenv($envVar);
        }
    }

    public function getLoadedFiles() {
        return $this->loadedFiles;
    }

    public function writeProfileToDotEnv($profile, $file='.env') {
        $tmp = $this->getEnvVars($profile);

        $res = file_put_contents($file, implode("\n", $tmp));
        if ($res === false) {
            throw new \Exception('Error while writing file .env');
        }
        return $file;
    }

    protected function getDecryptedFilecontent($encryptedFilename) {
        if (!is_file($encryptedFilename)) {
            throw new \Symfony\Component\Filesystem\Exception\FileNotFoundException("Could not find encrypted file $encryptedFilename");
        }
        if (!class_exists('\Vault\Vault')) {
            throw new \Exception('Please install aoepeople/vault');
        }
        try {
            return \Vault\Vault::open($encryptedFilename);
        } catch (\Exception $e) {
            throw new \Exception('Error decrypting '.$encryptedFilename, 0, $e);
        }
    }

    protected function isEncryptedFile($filename) {
        return substr($filename, -10) == '.encrypted';
    }

    protected function getEncryptedFileName($filename) {
        return $filename . '.encrypted';
    }

    protected function getFileContent($filename) {
        if ($this->isEncryptedFile($filename)) {
            return $this->getDecryptedFilecontent($filename);
        }
        if (!is_file($filename)) {
            // try if there's an encrpyted version of this file
            return $this->getDecryptedFilecontent($this->getEncryptedFileName($filename));
        }
        return file_get_contents($filename);
    }

    protected function loadFile($filename) {
        $configYaml = $this->getFileContent($filename);
        $config = \Symfony\Component\Yaml\Yaml::parse($configYaml);
        if (!isset($config['profiles'])) {
            throw new \Exception('Could not find "profiles" key');
        }
        if (!is_array($config['profiles']) || count($config['profiles']) == 0) {
            throw new \Exception('Could not find any profiles');
        }
        $this->loadedFiles[] = $filename;
        return $config['profiles'];
    }

    protected function findAllProfileFiles()
    {
        $files = array_merge(
            ['profiles.yml'],
            glob('profiles.*.yml'),
            glob('profiles.*.yml.encrypted')
        );
        return $files;
    }

    protected function getConfig() {
        if (is_null($this->config)) {
            $this->config = [];
            foreach ($this->findAllProfileFiles() as $file) {
                $this->config = array_merge(
                    $this->config,
                    $this->loadFile($file)
                );
            }
        }
        return $this->config;
    }

    protected function getEnvVars($profile) {
        $profileConfig = $this->getProfileConfig($profile);
        $mapping = [
            'region' => 'AWS_DEFAULT_REGION',
            'access_key' => 'AWS_ACCESS_KEY_ID',
            'secret_key' => 'AWS_SECRET_ACCESS_KEY',
            'assume_role' => 'AWS_ASSUME_ROLE'
        ];

        $tmp = [];
        $tmp[] = 'AWSINSPECTOR_PROFILE='.$profile;
        foreach ($mapping as $key => $value) {
            if (empty($profileConfig[$key])) {
                if ($key == 'assume_role') {
                    continue;
                }
                throw new \Exception('Mising configuration: ' . $key);
            }
            $tmp[] = $mapping[$key].'='.$profileConfig[$key];
        }
        return $tmp;
    }

}