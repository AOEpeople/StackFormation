<?php

namespace StackFormation\Exception;

class StackCannotBeUpdatedException extends \Exception
{

    protected $stackName;
    protected $state;

    public function __construct($stackName, $state, \Exception $previous=null)
    {
        $this->stackName = $stackName;
        $this->state = $state;
        parent::__construct("Stack '$stackName' not found (state: $state)", 0, $previous);
    }

    public function getStackName() {
        return $this->stackName;
    }

    public function getState() {
        return $this->state;
    }

}
