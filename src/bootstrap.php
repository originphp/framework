<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Load the Paths constants, if not already set (e.g. Tests)
 */

if (! defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
    define('ROOT', dirname(dirname(dirname(dirname(__DIR__)))));
}

if (! defined('APP')) {
    define('ORIGIN', ROOT . DS . 'vendor'. DS . 'originphp'. DS . 'framework');

    define('APP', ROOT . DS . 'app');
    define('CONFIG', ROOT . DS . 'config');
    define('DATABASE', ROOT . DS . 'database');
    define('PLUGINS', ROOT . DS . 'plugins');
    define('TESTS', ROOT . DS . 'tests');
    define('WEBROOT', ROOT . DS . 'public');

    define('TMP', ROOT . DS . 'tmp');
    define('LOGS', ROOT . DS . 'logs');
    define('CACHE', TMP . DS . 'cache');
}

error_reporting(E_ALL);
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
ini_set('error_log', LOGS);

date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');

require ORIGIN . DS . 'src' . DS . 'Exception' .DS  . 'Exception.php';

/**
 * Load our own autoloader
 */
require ORIGIN . DS . 'src' . DS  .'Core' . DS .'Autoloader.php';

/**
 * Load the composer autoloader
 */
require ROOT . DS . 'vendor' .DS . 'autoload.php';

/**
 * Error and Exception handling.
 */
if (PHP_SAPI === 'cli') {
    $ErrorHandler = new Origin\Console\ErrorHandler();
} else {
    $ErrorHandler = new Origin\Http\ErrorHandler();
}
$ErrorHandler->register();

require __DIR__ . DS . 'functions.php';

if (file_exists(CONFIG . DS . '.env.php')) {
    $result = include CONFIG . DS . '.env.php';
    foreach ($result as $key => $value) {
        $_ENV[$key] = $value;
    }
}

/**
 * Load Config
 */
require CONFIG . DS . 'bootstrap.php';
require CONFIG . DS . 'routes.php';
