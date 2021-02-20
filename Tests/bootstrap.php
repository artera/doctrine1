<?php

$_SERVER['DOCTRINE_DIR'] = realpath(dirname(__FILE__) . '/../');
define('DOCTRINE_DIR', $_SERVER['DOCTRINE_DIR']);

$startTime = time();

require_once __DIR__ . '/../vendor/autoload.php';
spl_autoload_register(['Doctrine_Core', 'modelsAutoload']);
