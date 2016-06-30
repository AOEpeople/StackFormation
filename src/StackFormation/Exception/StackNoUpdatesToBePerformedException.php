<?php

namespace StackFormation\Exception;

class StackNoUpdatesToBePerformedException extends \Exception
{

    protected $stackName;
    protected $state;

    public function __construct($stackName, \Exception $previous=null)
    {
        $this->stackName = $stackName;
        parent::__construct("No updates to be performened on stack $stackName", 0, $previous);
    }

    public function getStackName() {
        return $this->stackName;
    }

}
