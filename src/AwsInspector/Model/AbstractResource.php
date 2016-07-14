<?php

namespace AwsInspector\Model;

abstract class AbstractResource
{
    /**
     * @var array
     */
    protected $apiData = [];

    /**
     * @var \StackFormation\Profile\Manager
     */
    protected $profileManager = null;

    /**
     * @param array $apiData
     * @param \StackFormation\Profile\Manager $profileManager
     */
    public function __construct(array $apiData, \StackFormation\Profile\Manager $profileManager = null)
    {
        $this->apiData = $apiData;
        $this->profileManager = is_null($profileManager) ? new  \StackFormation\Profile\Manager() : $profileManager;
    }

    /**
     * @return array
     */
    public function getApiData()
    {
        return $this->apiData;
    }

    /**
     * @param array $mapping
     * @return array
     */
    public function extractData(array $mapping)
    {
        $result = [];
        foreach ($mapping as $fieldName => $expression) {
            $result[$fieldName] = \JmesPath\Env::search($expression, $this->apiData);
        }
        return $result;
    }

    /**
     * @param string $method
     * @param $args
     * @return mixed|null
     * @throws \Exception
     */
    public function __call($method, $args)
    {
        if (substr($method, 0, 3) != 'get') {
            $class = get_class($this);
            throw new \Exception("Invalid method '$method' (class: $class)");
        }

        $field = substr($method, 3);
        if (isset($this->apiData[$field])) {
            return $this->apiData[$field];
        }

        return null;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getAssocTags()
    {
        //if (!isset($this->apiData['Tags']) && !method_exists($this, 'getTags')) {
        //    throw new \Exception('Tags are not supported');
        //}
        return $this->convertToAssocArray($this->getTags() ?: []);
    }

    /**
     * @param string $key
     * @return sring|null
     */
    public function getTag($key)
    {
        $tags = $this->getAssocTags();
        return isset($tags[$key]) ? $tags[$key] : null;
    }

    /**
     * @param array $filter
     * @return bool
     * @throws \Exception
     */
    public function matchesTags(array $filter)
    {
        $tags = $this->getAssocTags();
        $patched = array_replace_recursive($tags, $filter);
        return $tags == $patched;
    }

    /**
     * @param array $tags
     * @return array
     */
    protected function convertToAssocArray(array $tags)
    {
        $assocTags = [];
        foreach ($tags as $data) {
            $assocTags[$data['Key']] = $data['Value'];
        }
        return $assocTags;
    }
}
