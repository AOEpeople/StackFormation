<?php

namespace StackFormation\PreProcessor;

/**
 * Class RootlineItem
 *
 * Since you not only need the reference to the parent(s) but also their key in relation to their parent
 * (e.g. in case you need to delete them), this rootline item represents the item itself (value) and the position (key)
 */
class RootlineItem {

    protected $key;
    protected $value;

    /**
     * @param string $key
     * @param mixed $value
     */
    public function __construct($key, $value) {
        $this->key = $key;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getKey() {
        return $this->key;
    }

    /**
     * @return mixed
     */
    public function getValue() {
        return $this->value;
    }
}
