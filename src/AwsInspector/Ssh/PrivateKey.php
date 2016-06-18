<?php

namespace AwsInspector\Ssh;

use Vault\Vault;

class PrivateKey {

    protected $privateKeyFile;

    protected $unlocked;

    protected static $keys=[];

    public static function get($privateKeyFile) {
        if (!isset(self::$keys[$privateKeyFile])) {
            self::$keys[$privateKeyFile] = new PrivateKey($privateKeyFile);
        }
        return self::$keys[$privateKeyFile];
    }

    protected function __construct($privateKeyFile)
    {
        if (is_file($privateKeyFile)) {
            $this->privateKeyFile = $privateKeyFile;
        } else {
            $encryptedPrivateKeyFile = $privateKeyFile . '.encrypted';
            $this->privateKeyFile = $privateKeyFile . '.unlocked';
            if (is_file($encryptedPrivateKeyFile)) {
                if (class_exists('\Vault\Vault')) {
                    $vault = new Vault();
                    $vault->decryptFile($encryptedPrivateKeyFile, $this->privateKeyFile);
                    chmod($this->privateKeyFile, 0600);
                    $this->unlocked = true;
                } else {
                    throw new \Exception('Please install aoepeople/vault');
                }
            } else {
                throw new \Exception('Could not find private key file ' . $privateKeyFile);
            }
        }
        $this->privateKeyFile = realpath($this->privateKeyFile);
    }

    public function getPrivateKeyFile() {
        return $this->privateKeyFile;
    }

    public function __destruct()
    {
        if ($this->unlocked) {
            unlink($this->privateKeyFile);
            $this->unlocked = null;
        }
    }



}