<?php

namespace StackFormation\Exception;

class StackNotFoundException extends \Exception
{

    public function __construct($stackName, \Exception $previous=null)
    {
        $message = "Stack '$stackName' not found";
        parent::__construct($message, 0, $previous);
    }

}
