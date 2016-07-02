<?php

namespace StackFormation\Exception;

use StackFormation\Blueprint;

class ValueResolverException extends \Exception
{

    protected $sourceBlueprint;
    protected $sourceType;
    protected $sourceKey;

    public function __construct($value, Blueprint $sourceBlueprint=null, $sourceType=null, $sourceKey=null, \Exception $previous = null)
    {
        $this->sourceBlueprint = $sourceBlueprint;
        $this->sourceType = $sourceType;
        $this->sourceKey = $sourceKey;

        if (!is_scalar($value)) {
            $value = var_export($value, true);
        }

        $message = "Error resolving value '$value'" . $this->getExceptionMessageAppendix();
        parent::__construct($message, 0, $previous);
    }

    /**
     * Craft exception message appendix
     *
     * @return string
     */
    protected function getExceptionMessageAppendix()
    {
        $tmp = [];
        if ($this->sourceBlueprint) { $tmp[] = 'Blueprint: ' . $this->sourceBlueprint->getName(); }
        if ($this->sourceType) { $tmp[] = 'Type:' . $this->sourceType; }
        if ($this->sourceKey) { $tmp[] = 'Key:' . $this->sourceKey; }
        if (count($tmp)) {
            return ' (' . implode(', ', $tmp) . ')';
        }
        return '';
    }

}
