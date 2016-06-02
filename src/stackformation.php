#!/usr/bin/env php
<?php

$i = 0;
do {
    $autoloader = __DIR__ . str_repeat('/..', $i) . '/vendor/autoload.php';
    $i++;
} while ($i < 6 && !is_file($autoloader));
require_once $autoloader;

use Symfony\Component\Console\Application;

$app = new Application('StackFormation', '@package_version@');

foreach (\StackFormation\CommandRegistry::getCommands() as $command) {
    $app->add($command);
}

// import AwsInspector commands for convenience reasons
if (class_exists('\AwsInspector\CommandRegistry')) {
    foreach (\AwsInspector\CommandRegistry::getCommands() as $command) {
        $app->add($command);
    }
}

$app->run();
