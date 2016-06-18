<?php

namespace AwsInspector\Model\Ec2;

class CollectionRegistry {

    protected static $registry = [];

    /**
     * @param array $tags
     * @return \AwsInspector\Model\Collection
     */
    public static function getCollection(array $tags=[]) {
        $hash = serialize($tags);
        if (!isset(self::$registry[$hash])) {
            $repository = new Repository();
            self::$registry[$hash] = $repository->findEc2InstancesByTags($tags);
        }
        return self::$registry[$hash];
    }


}