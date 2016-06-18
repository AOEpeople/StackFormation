<?php

namespace AwsInspector\Command\Ec2;

use Symfony\Component\Console\Command\Command;

class AbstractCommand extends Command
{

    protected function convertTags(array $tags) {
        $convertedTags=[];
        foreach ($tags as $value) {
            list($tagName, $tagValue) = explode(':', $value);
            $convertedTags[$tagName] = $tagValue;
        }
        return $convertedTags;
    }

}