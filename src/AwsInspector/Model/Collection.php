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
}
