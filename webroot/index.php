<?php
/**
 * Front Controller.
 */

/**
 * Start the Origin Bootstrap Process.
 */
require dirname(__DIR__) . '/origin/src/bootstrap.php';

$Dispatcher = new Origin\Core\Dispatcher();
$Dispatcher->start();
