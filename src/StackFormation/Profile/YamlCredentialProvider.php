<?php

namespace StackFormation\Profile;

use Aws\Credentials\Credentials;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class YamlCredentialProvider {

    protected $config;

    /**
     * @param $profile string
     * @return Credentials
     * @throws \Exception
     */
    public function getCredentialsForProfile($profile) {
        if (!$this->isValidProfile($profile)) {
            throw new \Exception("Invalid profile: $profile");
        }
        $config = $this->getConfig();
        $profileConfig = $config[$profile];
        if (empty($profileConfig['access_key'])) { throw new \Exception("Invalid access_key in profile $profile"); }
        if (empty($profileConfig['secret_key'])) { throw new \Exception("Invalid secret_key in profile $profile"); }
        return new Credentials(
            $profileConfig['access_key'],
            $profileConfig['secret_key']
        );
    }

    /**
     * @param $profile string
     * @return Credentials
     * @throws \Exception
     */
    public function getEnvVarsForProfile($profile) {
        $envVars = [];
        if (!$this->isValidProfile($profile)) {
            throw new \Exception("Invalid profile: $profile");
        }
        $config = $this->getConfig();
        $profileConfig = $config[$profile];
        if (false === empty($profileConfig['access_key'])){
            $envVars['AWS_ACCESS_KEY_ID'] = $profileConfig['access_key'];
            unset($profileConfig['access_key']);
        } else {
            throw new \Exception("Invalid access_key in profile $profile");
        }
        if (false === empty($profileConfig['secret_key'])) {
            $envVars['AWS_SECRET_ACCESS_KEY'] = $profileConfig['secret_key'];
            unset($profileConfig['secret_key']);
        } else {
            throw new \Exception("Invalid secret_key in profile $profile");
        }
        if(false === empty($profileConfig['region'])){
            $envVars['AWS_DEFAULT_REGION'] = $profileConfig['region'];
            unset($profileConfig['region']);
        }
        if(false === empty($profileConfig['filter'])){
            $envVars['STACKFORMATION_NAME_FILTER'] = $profileConfig['filter'];
            unset($profileConfig['filter']);
        }
        foreach ($profileConfig as $key => $value){
            if(false === (bool)preg_match('/^[a-z0-9-_]+/', $key)){
                throw new \Exception("Invalid environment variable in profile $profile");
            }else{
                $envVars[strtoupper(str_replace('-','_',$key))] = $value;
            }
        }
        return $envVars;
    }

    public function listAllProfiles() {
        return array_keys($this->getConfig());
    }


    public function isValidProfile($profile) {
        if (!is_string($profile) || empty($profile)) {
            throw new \InvalidArgumentException('Invalid profile');
        }
        $config = $this->getConfig();
        return isset($config[$profile]);
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
            try {
                $encryptedFilename = $this->getEncryptedFileName($filename);
                return $this->getDecryptedFilecontent($encryptedFilename);
            } catch (FileNotFoundException $e) {
                throw new FileNotFoundException("Could not find '$filename' or '$encryptedFilename'", 0, $e);
            }
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


}
