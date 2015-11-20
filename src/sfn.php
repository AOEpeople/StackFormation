#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Console\Application;

$app = new Application('Sfn', '@package_version@');
$app->add(new \Sfn\Command\Stack\DeployCommand());
$app->run();