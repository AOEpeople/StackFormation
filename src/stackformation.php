#!/usr/bin/env php
<?php

$i=0;
do {
    $autoloader = __DIR__ . str_repeat ('/..', $i) .  '/vendor/autoload.php';
    $i++;
} while ($i<6 && !is_file($autoloader));
require_once $autoloader;

use Symfony\Component\Console\Application;

$app = new Application('StackFormation', '@package_version@');

$app->add(new \StackFormation\Command\DeployCommand);
$app->add(new \StackFormation\Command\ListCommand);
$app->add(new \StackFormation\Command\ObserveCommand);
$app->add(new \StackFormation\Command\DeleteCommand);

$app->run();