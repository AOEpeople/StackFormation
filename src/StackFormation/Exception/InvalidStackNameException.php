<?php

namespace StackFormation\Exception;

class InvalidStackNameException extends \Exception
{

    protected $stackName;

    public function __construct($stackName)
    {
        $this->stackName = $stackName;
        $message = "Invalid stack name '$stackName'";
        parent::__construct($message, 0, $previous=null);
    }

    /**
     * @return string
     */
    public function getStackName()
    {
        return $this->stackName;
    }

}
