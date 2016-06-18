<?php

namespace AwsInspector\Ssh;

class Identity
{

    protected $privateKey;

    protected $keepIdentity;

    public function __construct(PrivateKey $privateKey, $keepIdentity=false)
    {
        $this->privateKey = $privateKey;
        $this->keepIdentity = $keepIdentity;
    }

    public function getPrivateKeyFile() {
        return $this->privateKey->getPrivateKeyFile();
    }

    public function loadIdentity()
    {
        if (!Agent::identityLoaded($this->getPrivateKeyFile())) {
            Agent::addIdentity($this->getPrivateKeyFile());
        }
        return $this;
    }

    public function removeIdentity()
    {
        if (!empty($this->getPrivateKeyFile()) && Agent::identityLoaded($this->getPrivateKeyFile())) {
            // echo "Removing identity {$this->unlockedPrivateKeyFile}\n";
            Agent::deleteIdentity($this->getPrivateKeyFile());
        }
        return $this;
    }

    public function __destruct()
    {
        if (!$this->keepIdentity) {
            $this->removeIdentity();
            // Remove control paths for ssh multiplexing. See \AwsInspector\Ssh->__toString()
            // TODO: deleting these files isn't enough. The mux needs to be closed.
            exec('rm ~/mux* 2> /dev/null');
        }
    }

}