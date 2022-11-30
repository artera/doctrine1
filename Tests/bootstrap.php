<?php

$SERVER['DOCTRINE_DIR'] = realpath(dirname(__FILE__) . '/../');
define('DOCTRINE_DIR', $SERVER['DOCTRINE_DIR']);

$startTime = time();

require_once __DIR__ . '/../vendor/autoload.php';
spl_autoload_register(['\Doctrine1\Core', 'modelsAutoload']);
