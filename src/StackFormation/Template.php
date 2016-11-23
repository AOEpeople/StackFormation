<?php

namespace StackFormation;

class Template
{

    protected $filepath;
    protected $cache;
    protected $stringPreProcessor;
    protected $treePreProcessor;
    protected $tree;

    public function __construct($filePath, PreProcessor\StringPreProcessor $stringPreProcessor = null, PreProcessor\TreePreProcessor $treePreProcessor = null)
    {
        if (!is_file($filePath)) {
            throw new \Symfony\Component\Filesystem\Exception\FileNotFoundException("File '$filePath' not found");
        }

        $this->filepath = $filePath;
        $this->cache = new \StackFormation\Helper\Cache();
        $this->stringPreProcessor = $stringPreProcessor ? $stringPreProcessor : new PreProcessor\StringPreProcessor();
        $this->treePreProcessor = $treePreProcessor ? $treePreProcessor : new PreProcessor\TreePreProcessor();
    }

    public function getFilePath()
    {
        return $this->filepath;
    }

    /**
     * @return array
     */
    public function getTree()
    {
        return $this->tree;
    }

    /**
     * @param array $tree
     */
    public function setTree(array $tree)
    {
        $this->tree = $tree;
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

    public function getProcessedTemplate()
    {
        return $this->cache->get(
            __METHOD__,
            function () {
                $this->treePreProcessor->process($this);
                return $this;
            }
        );
    }

    public function getData()
    {
        if (!$this->cache->has(__METHOD__)) {
            $fileContent = $this->getFileContent();

            if (\StackFormation\Helper\Div::isJson($fileContent)) {
                // TODO Just a workaround (need to be a single line, replace \n would also delete new line char in multiline strings
                $fileContent = str_replace("\n", "", $fileContent);
            }

            $yamlParser = new \Symfony\Component\Yaml\Parser();
            $this->tree = $yamlParser->parse($fileContent);

            $template = $this->getProcessedTemplate();

            if (!is_array($template->getTree())) {
                throw new TemplateDecodeException($template->getFilePath(), sprintf("Error decoding file '%s'", $template->getFilePath()));
            }
            if ($template->tree['AWSTemplateFormatVersion'] != '2010-09-09') {
                throw new TemplateInvalidException($template->getFilePath(), 'Invalid AWSTemplateFormatVersion');
            }

            $this->cache->set(__METHOD__, $template->tree);
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
