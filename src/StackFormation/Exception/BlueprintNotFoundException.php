<?php

namespace StackFormation\Exception;

class BlueprintNotFoundException extends \Exception
{

    protected $blueprintName;

    public function __construct($blueprintname, $code = 0, \Exception $previous = null)
    {
        $this->blueprintName = $blueprintname;
        $message = "Blueprint '$blueprintname' not found.";
        parent::__construct($message, $code, $previous);
    }

    public function getBlueprintName()
    {
        return $this->blueprintName;
    }

}
