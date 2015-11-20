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

$app->add(new \StackFormation\Command\Stack\DeployCommand);
$app->add(new \StackFormation\Command\Stack\ListCommand);
$app->add(new \StackFormation\Command\Stack\ObserveCommand);
$app->add(new \StackFormation\Command\Stack\DeleteCommand);

$app->run();