<?php

namespace StackFormation;

use \StackFormation\PreProcessor\StringPreProcessor;
use \StackFormation\PreProcessor\TreePreProcessor;

class Template
{
    protected $filepath;
    protected $cache;
    protected $stringPreProcessor;
    protected $treePreProcessor;

    public function __construct($filePath, StringPreProcessor $stringPreProcessor = null, TreePreProcessor $treePreProcessor = null)
    {
        if (!is_file($filePath)) {
            throw new \Symfony\Component\Filesystem\Exception\FileNotFoundException("File '$filePath' not found");
        }
        $this->filepath = $filePath;
        $this->cache = new \StackFormation\Helper\Cache();
        $this->stringPreProcessor = $stringPreProcessor ? $stringPreProcessor : new StringPreProcessor();
        $this->treePreProcessor = $treePreProcessor ? $treePreProcessor : new TreePreProcessor();
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
                $fileContent = file_get_contents($this->filepath);
                return $this->stringPreProcessor->process($fileContent);
            }
        );
    }

    public function getProcessedTemplate($fileContent)
    {
        return $this->cache->get(
            __METHOD__,
            function () use ($fileContent) {
                if (\StackFormation\Helper\Div::isJson($fileContent)) {
                    // TODO Just a workaround (need to be a single line, replace \n would also delete new line char in multiline strings
                    $fileContent = str_replace("\n", "", $fileContent);
                }

                $data = \Symfony\Component\Yaml\Yaml::parse($fileContent);
                return $this->treePreProcessor->process($data, $this->getBasePath());
            }
        );
    }

    public function getData()
    {
        if (!$this->cache->has(__METHOD__)) {
            $fileContent = $this->getFileContent();
            $data = $this->getProcessedTemplate($fileContent);
            if (!is_array($data)) {
                throw new TemplateDecodeException($this->getFilePath(), sprintf("Error decoding file '%s'", $this->getFilePath()));
            }
            if ($data['AWSTemplateFormatVersion'] != '2010-09-09') {
                throw new TemplateInvalidException($this->getFilePath(), 'Invalid AWSTemplateFormatVersion');
            }

            $this->cache->set(__METHOD__, $data);
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
