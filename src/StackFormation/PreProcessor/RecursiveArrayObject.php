<?php

namespace StackFormation\PreProcessor;

/**
 * Class RecursiveArrayObject
 *
 * PHP's ArrayObject isn't recursive (nested arrays will remain regular arrays),
 * this one will transform nested arrays into ArrayObjects...
 */
class RecursiveArrayObject extends \ArrayObject {

    /**
     * @param null $input
     * @param int $flags
     * @param string $iterator_class
     */
    public function __construct($input = null, $flags = self::ARRAY_AS_PROPS, $iterator_class = "ArrayIterator") {
        foreach ($input as $k=>$v) {
            $this->__set($k, $v);
        }
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value){
        if (is_array($value) || is_object($value)) {
            $this->offsetSet($name, (new self($value)));
        } else {
            $this->offsetSet($name, $value);
        }
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name){
        if ($this->offsetExists($name)) {
            return $this->offsetGet($name);
        } elseif (array_key_exists($name, $this)) {
            return $this[$name];
        } else {
            throw new \InvalidArgumentException(sprintf('$this have not prop `%s`',$name));
        }
    }

    /**
     * @return array
     */
    public function getArrayCopy()
    {
        $array = parent::getArrayCopy();
        $assocArray = false;
        foreach ($array as $key => $value) {
            if (is_string($key)) {
                $assocArray = true;
            }
            if ($value instanceof RecursiveArrayObject) {
                $array[$key] = $value->getArrayCopy();
            }
        }
        return $assocArray ? $array : array_values($array);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset($name){
        return array_key_exists($name, $this);
    }

    /**
     * @param string $name
     */
    public function __unset($name){
        unset($this[$name]);
    }
}
