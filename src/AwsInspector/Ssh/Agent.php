<?php

namespace AwsInspector\Ssh;

class Agent {

    public static function listIdentities() {
        exec('ssh-add -l', $output);
    }

    public static function addIdentity($privateKeyFile) {
        if (!is_file($privateKeyFile)) {
            throw new \Exception('Private key file not found.');
        }
        // exec('ssh-add ' . $privateKeyFile . ' >/dev/null 2>&1', $output);
        exec('ssh-add ' . $privateKeyFile, $output);
        var_dump($output);
    }

    /**
     * Check if the current private key is already loaded
     *
     * @param $privateKeyFile
     * @return bool
     */
    public static function identityLoaded($privateKeyFile) {
        $returnVar = null;
        exec('ssh-add -l | grep '. $privateKeyFile, $output, $returnVar);
        return ($returnVar == 0);
    }

    /**
     * Since deleting an entity requires the PUBLIC key instead of the private key (that's used for adding)
     * we're going to extract the public key first and write it into a temp file
     *
     * @param $privateKeyFile
     * @throws \Exception
     */
    public static function deleteIdentity($privateKeyFile) {
        if (!is_file($privateKeyFile)) {
            throw new \Exception('Private key file not found.');
        }
        exec('ssh-add -L | grep '. $privateKeyFile, $output);
        $tmpFile = tempnam(sys_get_temp_dir(), 'publickey_');
        file_put_contents($tmpFile, end($output));
        exec('ssh-add -d ' . $tmpFile . ' >/dev/null 2>&1', $output);
        unlink($tmpFile);
    }

    public static function deleteAllIdentities() {
        exec('ssh-add -D', $output);
    }

}