<?php
/**
 * Script to test the output from Doctrine_Cli
 *
 * @author Dan Bettles <danbettles@yahoo.co.uk>
 */

require_once dirname(__FILE__, 3) . '/lib/Doctrine.php';
spl_autoload_register(['Doctrine', 'autoload']);

require_once dirname(__FILE__) . '/TestTask02.php';

$cli = new Doctrine_Cli(['autoregister_custom_tasks' => false]);
$cli->run($_SERVER['argv']);
