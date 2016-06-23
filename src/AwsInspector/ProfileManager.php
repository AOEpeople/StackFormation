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

    protected function loadFromConfig($filename='profiles.yml') {
        if (!is_file($filename)) {
            $encryptedFilename = $filename.'.encrypted';
            if (is_file($encryptedFilename)) {
                if (class_exists('\Vault\Vault')) {
                    try {
                        $configYaml = \Vault\Vault::open($encryptedFilename);
                    } catch (\Exception $e) {
                        throw new \Exception('Error decrypting '.$encryptedFilename, 0, $e);
                    }
                } else {
                    throw new \Exception('Please install aoepeople/vault');
                }
            } else {
                throw new \Symfony\Component\Filesystem\Exception\FileNotFoundException("Could not find $filename or $encryptedFilename");
            }
        } else {
            $this->loadedFiles[] = $filename;
            $configYaml = file_get_contents($filename);
        }
        $config = \Symfony\Component\Yaml\Yaml::parse($configYaml);
        if (!isset($config['profiles'])) {
            throw new \Exception('Could not find "profiles" key');
        }
        if (!is_array($config['profiles']) || count($config['profiles']) == 0) {
            throw new \Exception('Could not find any profiles');
        }
        return $config['profiles'];
    }

    protected function getConfig() {
        if (is_null($this->config)) {
            $this->config = $this->loadFromConfig();
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