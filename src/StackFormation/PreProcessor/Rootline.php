<?php

namespace StackFormation\PreProcessor;

/**
 * Class Rootline
 *
 * The rootline shows gives the transformers access to the parent objects (all generations)
 * since sometimes you need to change things outside of the current value (e.g. when replacing the whole node)
 */
class Rootline extends \ArrayObject {

    /**
     * @return array
     */
    protected function getKeys() {
        return array_keys($this->getArrayCopy());
    }

    /**
     * @param $index
     * @return mixed
     */
    public function indexGet($index) {
        $keys = $this->getKeys();
        return $this->offsetGet($keys[$index]);
    }

    public function removeLast() {
        $keys = $this->getKeys();

        // TODO
        @$this->offsetUnset($keys[$this->count()]);
    }

    /**
     * @param int $generation
     * @return mixed
     */
    public function parent($generation = 1) {
        $keys = $this->getKeys();
        return $this->offsetGet($keys[$this->count() - $generation]);
    }
}
