<?php

namespace StackFormation;

class Template
{

    protected $filepath;
    protected $cache;
    protected $preProcessor;

    public function __construct($filePath, Preprocessor $preprocessor = null)
    {
        if (!is_file($filePath)) {
            throw new \Symfony\Component\Filesystem\Exception\FileNotFoundException("File '$filePath' not found");
        }
        $this->filepath = $filePath;
        $this->cache = new \StackFormation\Helper\Cache();
        $this->preProcessor = $preprocessor ? $preprocessor : new Preprocessor();
    }

    public function getFilePath()
    {
        return $this->filepath;
    }

    public function getFileContent()
    {
        return $this->cache->get(
            __METHOD__,
            function () {
                return file_get_contents($this->filepath);
            }
        );
    }

    public function getProcessedTemplate()
    {
        return $this->cache->get(
            __METHOD__,
            function () {
                return $this->preProcessor->process($this->getFileContent(), $this->getBasePath());
            }
        );
    }

    public function getData()
    {
        if (!$this->cache->has(__METHOD__)) {
            $templateBody = $this->getProcessedTemplate();
            $array = json_decode($templateBody, true);
            if (!is_array($array)) {
                throw new TemplateDecodeException($this->getFilePath(), sprintf("Error decoding file '%s'", $this->getFilePath()));
            }
            if ($array['AWSTemplateFormatVersion'] != '2010-09-09') {
                throw new TemplateInvalidException($this->getFilePath(), 'Invalid AWSTemplateFormatVersion');
            }

            $this->cache->set(__METHOD__, $array);
        }

        return $this->cache->get(__METHOD__);
    }

    public function getDescription()
    {
        $data = $this->getData();

        return isset($data['Description']) ? $data['Description'] : '';
    }

    public function getBasePath()
    {
        return dirname($this->getFilePath());
    }
}
