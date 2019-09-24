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

if (! defined('SRC')) {
    define('CONFIG', ROOT . DS . 'config');
    define('LOGS', ROOT . DS . 'logs');
    define('ORIGIN', ROOT . DS . 'vendor'. DS . 'originphp'. DS . 'framework');
    define('PLUGINS', ROOT . DS . 'plugins');
    define('SRC', ROOT . DS . 'app');
    define('APP', ROOT);
    define('TESTS', ROOT . DS . 'tests');
    define('TMP', ROOT . DS . 'tmp');
    define('WEBROOT', ROOT . DS . 'public');
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
require CONFIG . DS . 'log.php';
require CONFIG . DS . 'cache.php';
require CONFIG . DS . 'database.php';
require CONFIG . DS . 'storage.php';
require CONFIG . DS . 'email.php';
require CONFIG . DS . 'queue.php';
require CONFIG . DS . 'routes.php';
