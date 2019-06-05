<?php

error_reporting(E_ALL);
ini_set('display_errors', true);

define('DS', DIRECTORY_SEPARATOR);
define('ROOT',dirname(dirname(dirname(dirname(__DIR__)))));
define('CONFIG', ROOT . '/vendor/originphp/framework/tests/TestApp/config');
define('LOGS', sys_get_temp_dir());
define('ORIGIN', ROOT . '/vendor/originphp/framework');
define('SRC', ORIGIN . '/tests/TestApp/src');
define('TESTS', ORIGIN . '/tests/TestApp/tests');
define('APP', ORIGIN . '/tests/TestApp');
define('PLUGINS', ORIGIN . DS . 'tests/TestApp/plugins');
define('TMP', sys_get_temp_dir());
define('WEBROOT', ROOT . '/public');

@mkdir(TMP . '/cache');
require ORIGIN . '/src/bootstrap.php';