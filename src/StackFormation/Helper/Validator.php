<?php

namespace StackFormation\Helper;

use StackFormation\Exception\InvalidStackNameException;

class Validator
{


    public static function validateStackname($stackName)
    {
        if (!is_string($stackName)) {
            throw new \InvalidArgumentException('Invalid stack name (must be a string)');
        }
        // A stack name can contain only alphanumeric characters (case sensitive) and hyphens.
        // It must start with an alphabetic character and cannot be longer than 128 characters.
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9-]{0,127}$/', $stackName)) {
            throw new InvalidStackNameException($stackName);
        }
    }

    public static function validateTags(array $tags)
    {
        // @see http://docs.aws.amazon.com/AWSEC2/latest/UserGuide/Using_Tags.html#tag-restrictions
        if (count($tags) > 10) {
            throw new \Exception('No more than 10 tags are allowed');
        }
        foreach ($tags as $tag) {
            // key
            if (!isset($tag['Key'])) {
                throw new \Exception('Tag key is missing');
            }
            $key = $tag['Key'];
            if (strlen($key) > 127) {
                throw new \Exception('Keys cannot be longer than 127 characters');
            }
            if (!preg_match('/^[a-zA-Z0-9_\-+=:\/@\.]{1,127}$/', $key)) {
                throw new \Exception("Invalid characters in key '$key'");
            }
            if (strpos($key, 'aws:') === 0) {
                throw new \Exception('The aws: prefix cannot be used for keys');
            }

            // value
            if (!isset($tag['Value'])) {
                throw new \Exception('Tag value is missing');
            }
            $value = $tag['Value'];
            if (strlen($value) > 255) {
                throw new \Exception('Values cannot be longer than 255 characters');
            }
            if (!preg_match('/^[a-zA-Z0-9_\-+=:\/@\.]{1,255}$/', $value)) {
                throw new \Exception("Invalid characters in value '$value' (key: $key)");
            }
        }
    }
}
