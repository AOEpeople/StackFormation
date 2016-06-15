<?php

$i = 0;
do {
    $autoloader = __DIR__ . str_repeat('/..', $i) . '/vendor/autoload.php';
    $i++;
} while ($i < 6 && !is_file($autoloader));

$loader = require $autoloader; /* @var $loader \Composer\Autoload\ClassLoader */
$loader->setUseIncludePath(true);
define('FIXTURE_ROOT', __DIR__ . '/fixtures/');