<?php

namespace AwsInspector\Ssh;

class LocalConnection extends Connection
{

    public function __construct()
    {
    }

    public function __toString()
    {
        return '';
    }

    /**
     * Interactive connection
     */
    public function connect()
    {
       throw new \Exception('Not supported');
    }


}