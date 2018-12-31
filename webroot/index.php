<?php
/**
 * Front Controller.
 */
define('START_TIME', microtime(true));

error_reporting(E_ALL);
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);

/**
 * Start the Origin Bootstrap Process.
 */
require dirname(__DIR__).'/origin/src/bootstrap.php';

$Dispatcher = new Origin\Core\Dispatcher();
$Dispatcher->start();
