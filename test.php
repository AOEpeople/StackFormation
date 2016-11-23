<?php

/**
 * Class RecursiveArrayObject
 *
 * PHP's ArrayObject isn't recursive (nested arrays will remain regular arrays),
 * this one will transform nested arrays into ArrayObjects...
 */
class RecursiveArrayObject extends ArrayObject {
    public function __construct($input = null, $flags = self::ARRAY_AS_PROPS, $iterator_class = "ArrayIterator") {
        foreach ($input as $k=>$v) {
            $this->__set($k, $v);
        }
    }
    public function __set($name, $value){
        if (is_array($value) || is_object($value)) {
            $this->offsetSet($name, (new self($value)));
        } else {
            $this->offsetSet($name, $value);
        }
    }
    public function __get($name){
        if ($this->offsetExists($name)) {
            return $this->offsetGet($name);
        } elseif (array_key_exists($name, $this)) {
            return $this[$name];
        } else {
            throw new \InvalidArgumentException(sprintf('$this have not prop `%s`',$name));
        }
    }
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
    public function __isset($name){
        return array_key_exists($name, $this);
    }
    public function __unset($name){
        unset($this[$name]);
    }
}

/**
 * Class Rootline
 *
 * The rootline shows gives the transformers access to the parent objects (all generations)
 * since sometimes you need to change things outside of the current value (e.g. when replacing the whole node)
 */
class Rootline extends \ArrayObject {
    protected function getKeys() {
        return array_keys($this->getArrayCopy());
    }
    public function indexGet($index) {
        $keys = $this->getKeys();
        return $this->offsetGet($keys[$index]);
    }
    public function removeLast() {
        $keys = $this->getKeys();
        $this->offsetUnset($keys[$this->count()]);
    }
    public function parent($generation=1) {
        $keys = $this->getKeys();
        return $this->offsetGet($keys[$this->count() - $generation]);
    }
}


/**
 * Class RootlineItem
 *
 * Since you not only need the reference to the parent(s) but also their key in relation to their parent
 * (e.g. in case you need to delete them), this rootline item represents the item itself (value) and the position (key)
 */
class RootlineItem {
    protected $key;
    protected $value;
    public function __construct($key, $value) {
        $this->key = $key;
        $this->value = $value;
    }
    public function getKey() {
        return $this->key;
    }
    public function getValue() {
        return $this->value;
    }
}

/**
 * This is where the magic happens
 *
 * @param RecursiveArrayObject $array
 * @param array $transformers (poor-mans pipeline implementatio...)
 * @param string $parentPath (INTERNAL USE ONLY - when being called recursively)
 * @param Rootline|null $rootline (INTERNAL USE ONLY - when being called recursively)
 * @return bool (true indicates that something has changed, false shows that nothing has been touched)
 */
function traverse(RecursiveArrayObject $array, array $transformers, $parentPath='', Rootline $rootline=null) {
    if (is_null($rootline)) {
        $rootline = new Rootline();
    }
    foreach ($array as $key => $value) {
        $path = $parentPath . '/' . $key;
        if ($value instanceof RecursiveArrayObject) {

            // add element to the rootline stack
            $rootline->append(new RootlineItem($key, $value));
            echo "$path: ARRAY\n";
            if (traverse($value, $transformers, $parentPath . '/' . $key, $rootline)) {
                // if somethine has changed (return value true) we abort and start over
                // since the object structure is different now which will confusue the iterators
                return true;
            }

            // remove element from the rootline stack
            $rootline->removeLast();

        } else {
            echo "$path: $value\n";
        }

        foreach ($transformers as $transformer) {
            if ($transformer($path, $value, $rootline)) {
                // if somethine has changed (return value true) we abort and start over
                // since the object structure is different now which will confusue the iterators
                return true;
            }
        }

    }
    return false;
}

/**
 * Example implementation of a transformer.
 * This one converts "Port" into "FromPort" and "ToPort" and will resolve comma-separated lists
 *
 * @param $path
 * @param $value
 * @param Rootline $rootLineReferences
 * @return bool
 */
function expandCslPort($path, $value, Rootline $rootLineReferences) {
    if (!preg_match('+/SecurityGroupIngress/.*/Port$+', $path)) {
        return false;  // indicate that nothing has been touched
    }

    $parentRootlineItem = $rootLineReferences->parent(1); /* @var $parentRootlineItem RootlineItem */
    $parent = $parentRootlineItem->getValue(); /* @var $parent RecursiveArrayObject */

    $grandParentRootlineItem = $rootLineReferences->parent(2); /* @var $grandParentRootlineItem RootlineItem */
    $grandParent = $grandParentRootlineItem->getValue(); /* @var $grandParent RecursiveArrayObject */

    // remove original item
    $grandParent->offsetUnset($parentRootlineItem->getKey());

    // add a new line for every csl item
    foreach (explode(',', $value) as $port) {
        $newItem = clone $parent;
        $newItem->FromPort = $port;
        $newItem->ToPort = $port;
        $newItem->offsetUnset('Port');
        $grandParent->append($newItem);
    }

    return true; // indicate that something has changed
}

/**
 * Example implementation of a transformer
 * This one will resolve comma-separated list of IP addresses
 *
 * @param $path
 * @param $value
 * @param Rootline $rootLineReferences
 * @return bool
 */
function expandCidrIp($path, $value, Rootline $rootLineReferences) {
    if (!preg_match('+/SecurityGroupIngress/.*/CidrIp+', $path) || strpos($value, ',') === false) {
        return false; // indicate that nothing has been touched
    }

    $parentRootlineItem = $rootLineReferences->parent(1); /* @var $parentRootlineItem RootlineItem */
    $parent = $parentRootlineItem->getValue(); /* @var $parent RecursiveArrayObject */

    $grandParentRootlineItem = $rootLineReferences->parent(2); /* @var $grandParentRootlineItem RootlineItem */
    $grandParent = $grandParentRootlineItem->getValue(); /* @var $grandParent RecursiveArrayObject */

    // remove original item
    $grandParent->offsetUnset($parentRootlineItem->getKey());

    // add a new line for every csl item
    foreach (explode(',', $value) as $cidrIp) {
        $newItem = clone $parent;
        $newItem->CidrIp = $cidrIp;
        $grandParent->append($newItem);
    }
    return true; // indicate that something has changed
}

$cloudFormationYamlTemplate = <<<TEMPLATE
AWSTemplateFormatVersion: "2010-09-09"
Description: A sample template
Parameters:
  FilePath:
    Type: String
Resources:
  InstanceSecurityGroup:
    Type: "AWS::EC2::SecurityGroup"
    Properties:
      GroupDescription: "Enable HTTP access on the configured port"
      VpcId:
        Ref: "VpcId"
      SecurityGroupIngress:
        -
          IpProtocol: "tcp"
          Port: "80,443"
          CidrIp: "1.1.1.1/1,2.2.2.2/2,3.3.3.3/3"
TEMPLATE;




// find composer autoloader (only needed for \Symfony\Component\Yaml that already comes with StackFormation
$i = 0;
do { $autoloader = __DIR__ . str_repeat('/..', $i) . '/vendor/autoload.php'; $i++; } while ($i < 6 && !is_file($autoloader));
require_once $autoloader;

// Yaml -> array
$a = \Symfony\Component\Yaml\Yaml::parse($cloudFormationYamlTemplate);

// array -> ArrayObject (so we can restructure it since all the child elements are passed by reference)
$ao = new RecursiveArrayObject($a, ArrayObject::ARRAY_AS_PROPS);

// traverse the object (depth search) and call all transformers on every node. If any transformer changes something start over
$c = 0;
while (traverse($ao, ['expandCslPort', 'expandCidrIp'])) {
    if ($c++ > 100) { throw new Exception('Too many iteraitions. Are we stuck in a loop here?'); }
    echo "=====> Changes detected. Repeating...\n";
}
echo "=====> DONE...\n";

// output result
echo \Symfony\Component\Yaml\Yaml::dump($ao->getArrayCopy(), 100);
