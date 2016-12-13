<?php

namespace StackFormation\PreProcessor;

use StackFormation\Helper\Pipeline;

class TreePreProcessor
{
    const MAX_JS_FILE_INCLUDE_SIZE = 4096;

    /**
     * @var Pipeline
     */
    protected $pipeline = null;

    /**
     * @param array $data
     * @param string $basePath
     * @return array
     * @throws \Exception
     */
    public function process(array $data, $basePath)
    {
        // array -> ArrayObject (so we can restructure it since all the child elements are passed by reference)
        $data = new RecursiveArrayObject($data, \ArrayObject::ARRAY_AS_PROPS);

        $stageClasses = [
            '\StackFormation\PreProcessor\Stage\Tree\ExpandCslPort',
            '\StackFormation\PreProcessor\Stage\Tree\ExpandCidrIp',
            '\StackFormation\PreProcessor\Stage\Tree\InjectFilecontent',

            # TODO, check also if we still need that
            #'\StackFormation\PreProcessor\Stage\Tree\ParseRefInDoubleQuotedStrings',
            #'\StackFormation\PreProcessor\Stage\Tree\Base64encodedJson',
            #'\StackFormation\PreProcessor\Stage\Tree\Split',
            #'\StackFormation\PreProcessor\Stage\Tree\ReplaceFnGetAttr',
            #'\StackFormation\PreProcessor\Stage\Tree\ReplaceRef',
            #'\StackFormation\PreProcessor\Stage\Tree\ReplaceMarkers',
        ];

        $this->pipeline = new Pipeline();
        foreach ($stageClasses as $stageClass) {
            $this->pipeline->addStage(new $stageClass($this, $basePath));
        }

        // traverse the object (depth search) and call all transformers on every node. If any transformer changes something start over
        $c = 0;
        while ($this->traverse($data)) {
            if ($c++ > 100) { throw new \Exception('Too many iteraitions. Are we stuck in a loop here?'); }
            // Changes detected. Repeating ...
        }
        return $data->getArrayCopy();
    }

    /**
     * This is where the magic happens
     *
     * @param RecursiveArrayObject $array
     * @param string $parentPath (INTERNAL USE ONLY - when being called recursively)
     * @param Rootline|null $rootline (INTERNAL USE ONLY - when being called recursively)
     * @return bool (true indicates that something has changed, false shows that nothing has been touched)
     */
    function traverse(RecursiveArrayObject $array, $parentPath = '', Rootline $rootline = null) {
        if (null === $rootline) {
            $rootline = new Rootline();
        }

        foreach ($array as $key => $value) {
            $path = $parentPath . '/' . $key;
            if ($value instanceof RecursiveArrayObject) {
                // add element to the rootline stack
                $rootline->append(new RootlineItem($key, $value));

                if ($this->traverse($value, $parentPath . '/' . $key, $rootline)) {
                    // if somethine has changed (return value true) we abort and start over
                    // since the object structure is different now which will confusue the iterators
                    return true;
                }

                // remove element from the rootline stack
                $rootline->removeLast();
            }

            foreach ($this->pipeline->getStages() as $stage) {
                if ($stage->invoke($path, $value, $rootline)) {
                    // if somethine has changed (return value true) we abort and start over
                    // since the object structure is different now which will confusue the iterators
                    return true;
                }
            }
        }

        return false;
    }
}
