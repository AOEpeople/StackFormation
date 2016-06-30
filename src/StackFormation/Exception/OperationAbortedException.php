<?php

namespace StackFormation\Exception;

class OperationAbortedException extends \Exception
{
    public function __construct($operation, $reason, $code = 0, \Exception $previous = null)
    {
        $message = "Operation '$operation' aborted by user (Reason: $reason)";
        parent::__construct($message, $code, $previous);
    }

}
