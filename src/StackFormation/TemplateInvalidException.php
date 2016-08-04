<?php
namespace StackFormation;

class TemplateInvalidException extends \Exception
{
    protected $templateFile;

    public function __construct($templateFile, $message, \Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);

        $this->templateFile = $templateFile;
    }

    public function getTemplateFile()
    {
        return $this->templateFile;
    }
}
