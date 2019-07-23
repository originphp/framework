<?php

error_reporting(E_ALL);
ini_set('display_errors', true);

define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__DIR__));
define('ORIGIN', dirname(__DIR__));
define('CONFIG', ORIGIN . DS . 'tests'. DS . 'TestApp' . DS .'config');
define('LOGS', sys_get_temp_dir());

define('SRC', ORIGIN . DS . 'tests' . DS . 'TestApp' . DS .'src');
define('TESTS', ORIGIN . DS . 'tests' . DS . 'TestApp' . DS .'tests');
define('APP', ORIGIN . DS . 'tests' . DS . 'TestApp');
define('PLUGINS', ORIGIN . DS . 'tests' . DS . 'TestApp' . DS . 'plugins');
define('TMP', sys_get_temp_dir());
define('WEBROOT', ROOT . DS . 'public');

if (! file_exists(TMP . DS . 'cache')) {
    @mkdir(TMP . DS . 'cache');
}

require ORIGIN . DS . 'src' . DS . 'bootstrap.php';
