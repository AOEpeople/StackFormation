<?php

namespace AwsInspector\Model\Ec2;

class Factory
{

    public static function create(array $apiData)
    {
        $className = '\AwsInspector\Model\Ec2\Instance';

        // override base class
        $type = self::extractValue('Type', $apiData);
        if (!empty($type) && isset($GLOBALS['Ec2InstanceFactory'])) {
            if (isset($GLOBALS['Ec2InstanceFactory'][$type])) {
                $className = $GLOBALS['Ec2InstanceFactory'][$type];
            } elseif (isset($GLOBALS['Ec2InstanceFactory']['__DEFAULT__'])) {
                $className = $GLOBALS['Ec2InstanceFactory']['__DEFAULT__'];
            }
        }

        $instance = new $className($apiData);
        if (!$instance instanceof \AwsInspector\Model\Ec2\Instance) {
            throw new \Exception('Invalid class');
        }
        return $instance;
    }

    public static function extractValue($tagKey, array $entity)
    {
        if (!isset($entity['Tags'])) {
            return null;
        }
        $tags = $entity['Tags'];
        foreach ($tags as $tag) {
            if ($tag['Key'] === $tagKey) {
                return $tag['Value'];
            }
        }
        return null;
    }

}
