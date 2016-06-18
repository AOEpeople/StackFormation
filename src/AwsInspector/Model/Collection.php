<?php

namespace AwsInspector\Model;

class Collection extends \SplObjectStorage
{

    public function getFirst() {
        $this->rewind();
        return $this->current();
    }

}