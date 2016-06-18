<?php

namespace AwsInspector\Command\Ec2;

use AwsInspector\Finder;
use AwsInspector\Model\Ec2\Instance;
use AwsInspector\Model\Ec2\Repository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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