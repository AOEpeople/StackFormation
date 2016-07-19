<?php

namespace AwsInspector\Model;

class Collection extends \SplObjectStorage
{
    /**
     * @return object
     */
    public function getFirst() {
        $this->rewind();
        return $this->current();
    }

    public function flatten($method) {
        $flattenedObjects = [];
        foreach ($this as $item) {
            $flattenedObjects[] = $item->$method();
        }
        return $flattenedObjects;
    }
}
