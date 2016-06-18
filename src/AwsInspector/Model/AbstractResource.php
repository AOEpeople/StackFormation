<?php

namespace AwsInspector\Model;


abstract class AbstractResource
{

    protected $apiData;

    public function __construct(array $apiData)
    {
        $this->apiData = $apiData;
    }

    public function getApiData()
    {
        return $this->apiData;
    }

    public function extractData(array $mapping)
    {
        $result = [];
        foreach ($mapping as $fieldName => $expression) {
            $result[$fieldName] = \JmesPath\Env::search($expression, $this->apiData);
        }
        return $result;
    }

    public function __call($method, $args)
    {
        if (substr($method, 0, 3) == 'get') {
            $field = substr($method, 3);
            if (isset($this->apiData[$field])) {
                return $this->apiData[$field];
            } else {
                return null;
            }
        }
        throw new \Exception('Invalid method');
    }

    public function getAssocTags()
    {
        if (!isset($this->apiData['Tags']) && !method_exists($this, 'getTags')) {
            throw new \Exception('Tags are not supported');
        }
        return $this->convertToAssocArray($this->getTags());
    }

    public function getTag($key)
    {
        $tags = $this->getAssocTags();
        return isset($tags[$key]) ? $tags[$key] : null;
    }

    public function matchesTags(array $filter)
    {
        $elbTags = $this->getAssocTags();
        $patched = array_replace_recursive($elbTags, $filter);
        return $elbTags == $patched;
    }

    protected function convertToAssocArray(array $tags)
    {
        $assocTags = [];
        foreach ($tags as $data) {
            $assocTags[$data['Key']] = $data['Value'];
        }
        return $assocTags;
    }

}