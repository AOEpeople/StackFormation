<?php
namespace StackFormation;

class PrefixedTemplate extends Template
{
    protected $prefix;

    /**
     * presudo parameters which should not be prefixed
     *
     * @var array
     */
    protected $pseudoParameters = [
            'AWS::AccountId',
            'AWS::NotificationARNs',
            'AWS::NoValue',
            'AWS::Region',
            'AWS::StackId',
            'AWS::StackName'];

    /**
     * PrefixedTemplate constructor.
     *
     * @param string            $prefix
     * @param string            $filePath
     * @param Preprocessor|null $preprocessor
     */
    public function __construct($prefix, $filePath, Preprocessor $preprocessor = null)
    {
        parent::__construct($filePath, $preprocessor);

        $this->prefix = trim($prefix);
    }

    public function getProcessedTemplate()
    {
        if ($this->prefix) {
            if (!$this->cache->has(__METHOD__)) {
                $content = parent::getProcessedTemplate();
                $content = $this->updateRef($this->prefix, $content);
                $content = $this->updateDependsOn($this->prefix, $content);
                $content = $this->updateDependsOnMultiple($this->prefix, $content);
                $content = $this->updateFnGetAtt($this->prefix, $content);
                $content = $this->updateFnFindInMap($this->prefix, $content);
                $this->cache->set(__METHOD__, $content);
            }

            return $this->cache->get(__METHOD__);
        } else {
            return parent::getProcessedTemplate();
        }
    }

    public function getData()
    {
        if ($this->prefix) {
            if (!$this->cache->has(__METHOD__)) {
                $data = parent::getData();

                foreach ($data as $topLevelKey => $topLevelData) {
                    if (is_array($topLevelData)) {
                        $prefixedData = [];
                        foreach ($topLevelData as $key => $value) {
                            $prefixedData[$this->prefix . $key] = $value;
                        }
                        $array[$topLevelKey] = $prefixedData;
                    }
                }
                $this->cache->set(__METHOD__, $data);
            }

            return $this->cache->get(__METHOD__);
        } else {
            return parent::getData();
        }
    }

    /**
     * @param $prefix
     * @param $template
     *
     * @return mixed
     */
    protected function updateRef($prefix, $template)
    {
        // Update all { "Ref": "..." }
        $template = preg_replace_callback(
            '/\{\s*"Ref"\s*:\s*"([a-zA-Z0-9:]+?)"\s*\}/',
            function ($matches) use ($prefix) {
                if(true === in_array($matches[1], $this->pseudoParameters)){
                    return '{"Ref":"' . $matches[1] . '"}';
                }{
                    return '{"Ref":"' . $prefix . $matches[1] . '"}';
                }

            },
            $template
        );

        return $template;
    }

    /**
     * @param $prefix
     * @param $template
     *
     * @return mixed
     */
    protected function updateDependsOn($prefix, $template)
    {
        // Update all { "DependsOn": "..." }
        $template = preg_replace_callback(
            '/\"DependsOn"\s*:\s*"([a-zA-Z0-9:]+?)"/',
            function ($matches) use ($prefix) {
                return '"DependsOn":"' . $prefix . $matches[1] . '"';
            },
            $template
        );

        return $template;
    }

    /**
     * @param $prefix
     * @param $template
     *
     * @return mixed
     */
    protected function updateDependsOnMultiple($prefix, $template)
    {
        // Update all { "DependsOn": ["...", "...", ...] }
        $template = preg_replace_callback(
            '/"DependsOn"\s*:\s*\[(.*)\]/s',
            function ($matches) use ($prefix) {
                $dependencies = $matches[1];
                $dependencies = preg_replace_callback(
                    '/"([a-zA-Z0-9:]+?)"/',
                    function ($matches) use ($prefix) {
                        return '"' . $prefix . $matches[1] . '"';
                    },
                    $dependencies
                );

                return '"DependsOn":[' . $dependencies . ']';
            },
            $template
        );

        return $template;
    }

    /**
     * @param $prefix
     * @param $template
     *
     * @return mixed
     */
    protected function updateFnGetAtt($prefix, $template)
    {
        //  Update all "Fn::GetAtt": ["...", "..."] }
        $template = preg_replace_callback(
            '/"Fn::GetAtt"\s*:\s*\[\s*"([a-zA-Z0-9:]+?)"/',
            function ($matches) use ($prefix) {
                return '"Fn::GetAtt": ["' . $prefix . $matches[1] . '"';
            },
            $template
        );

        return $template;
    }

    /**
     * @param $prefix
     * @param $template
     *
     * @return mixed
     */
    protected function updateFnFindInMap($prefix, $template)
    {
        //  Update all "Fn::FindInMap": ["...", "..."] }
        $template = preg_replace_callback(
            '/"Fn::FindInMap"\s*:\s*\[\s*"([a-zA-Z0-9:]+?)"/',
            function ($matches) use ($prefix) {
                return '"Fn::FindInMap": ["' . $prefix . $matches[1] . '"';
            },
            $template
        );

        return $template;
    }
}
