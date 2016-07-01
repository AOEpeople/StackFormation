<?php

namespace StackFormation;

class Template {

    protected $filepath;
    protected $cache;
    protected $preProcessor;

    public function __construct($filePath, Preprocessor $preprocessor=null)
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
        return $this->cache->get(__METHOD__, function() {
           return file_get_contents($this->filepath);
        });
    }

    public function getProcessedTemplate()
    {
        return $this->cache->get(__METHOD__, function() {
            return $this->preProcessor->processJson($this->getFileContent(), $this->getBasePath());
        });
    }

    public function getDecodedJson()
    {
        return $this->cache->get(__METHOD__, function() {
            return json_decode($this->getProcessedTemplate(), true);
        });
    }

    public function getDescription()
    {
        $data = $this->getDecodedJson();
        return isset($data['Description']) ? $data['Description'] : '';
    }

    public function getBasePath()
    {
        return dirname($this->getFilePath());
    }


}