<?php
/**
 * Front Controller.
 */
define('START_TIME', microtime(true));

define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(dirname(__FILE__)));

define('CONFIG', ROOT . DS . 'config');
define('LOGS', ROOT . DS . 'logs');
define('ORIGIN', ROOT . DS . 'origin');
define('PLUGINS', ROOT . DS . 'plugins');
define('SRC', ROOT . DS . 'src');
define('TMP', ROOT . DS . 'tmp');
define('WEBROOT', ROOT . DS . 'webroot');

/**
 * Start the Origin Bootstrap Process.
 */
require dirname(__DIR__).'/origin/src/bootstrap.php';

$Dispatcher = new Origin\Core\Dispatcher();
$Dispatcher->start();
