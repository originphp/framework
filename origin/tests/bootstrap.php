<?php

error_reporting(E_ALL);
ini_set('display_errors', true);

define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(dirname(dirname(__FILE__))));

define('CONFIG', ROOT . DS . 'origin' . DS . 'tests' . DS . 'TestApp' . DS . 'config');
define('LOGS', ROOT . DS . 'logs');
define('ORIGIN', ROOT . DS . 'origin');
define('SRC', ORIGIN . DS . 'tests' . DS . 'TestApp' . DS . 'src');
define('PLUGINS', ORIGIN . DS . 'tests' . DS . 'TestApp' . DS . 'plugins');
define('TMP', ROOT . DS . 'tmp');
define('WEBROOT', ROOT . DS . 'webroot');

require ORIGIN . '/src/bootstrap.php';
