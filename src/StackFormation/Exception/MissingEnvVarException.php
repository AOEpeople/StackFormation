<?php

namespace StackFormation\Exception;

class MissingEnvVarException extends \Exception
{

    protected $envVar;

    public function __construct($varName, $code = 0, \Exception $previous = null)
    {
        $this->envVar = $varName;
        $message = "Environment variable '$varName' not found";
        parent::__construct($message, $code, $previous);
    }

    public function getEnvVar()
    {
        return $this->envVar;
    }

}
