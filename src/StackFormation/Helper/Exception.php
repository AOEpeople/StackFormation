<?php

namespace StackFormation\Helper;

class Exception
{

    public static function extractMessage(\Aws\CloudFormation\Exception\CloudFormationException $exception)
    {
        $message = (string)$exception->getResponse()->getBody();
        $xml = simplexml_load_string($message);
        if ($xml !== false && $xml->Error->Message) {
            return $xml->Error->Message;
        }
        return $exception->getMessage();
    }

    public static function refineException(\Aws\CloudFormation\Exception\CloudFormationException $exception)
    {
        $message = self::extractMessage($exception);
        $matches = [];
        if (preg_match('/^Stack \[(.+)\] does not exist$/', $message, $matches)) {
            return new \StackFormation\Exception\StackNotFoundException($matches[1], $exception);
        }
        if (preg_match('/.+stack\/(.+)\/.+is in ([A-Z_]+) state and can not be updated./', $message, $matches)) {
            return new \StackFormation\Exception\StackCannotBeUpdatedException($matches[1], $matches[2], $exception);
        }
        if (strpos($message, 'No updates are to be performed.') !== false) {
            return new \StackFormation\Exception\StackNoUpdatesToBePerformedException('TBD');
        }
        return new \StackFormation\Exception\CleanCloudFormationException($message, 0, $exception);
    }
    
}
