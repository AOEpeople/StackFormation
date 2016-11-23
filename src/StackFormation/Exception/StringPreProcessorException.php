<?php

namespace StackFormation\Exception;

use StackFormation\Template;

class StringPreProcessorException extends \Exception
{

    /**
     * @param Template $template
     * @param \Exception|null $previous
     */
    public function __construct($string, \Exception $previous = null)
    {
        // TODO
        parent::__construct($string, 1, $previous);
    }
}
