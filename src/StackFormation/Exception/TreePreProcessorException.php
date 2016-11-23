<?php

namespace StackFormation\Exception;

use StackFormation\Template;

class TreePreProcessorException extends \Exception
{

    /**
     * @param Template $template
     * @param \Exception|null $previous
     */
    public function __construct(Template $template, \Exception $previous = null)
    {
        // TODO
        parent::__construct($template->getFilePath(), 1, $previous);
    }
}
