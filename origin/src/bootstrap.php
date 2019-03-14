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
 * FrontController.
 */

/**
 * Load the Paths constants, if not already set (e.g. Tests)
 */
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
    define('ROOT', dirname(dirname(dirname(__FILE__))));
    define('CONFIG', ROOT . DS . 'config');
    define('LOGS', ROOT . DS . 'logs');
    define('ORIGIN', ROOT . DS . 'origin');
    define('PLUGINS', ROOT . DS . 'plugins');
    define('SRC', ROOT . DS . 'src');
    define('TMP', ROOT . DS . 'tmp');
    define('WEBROOT', ROOT . DS . 'public');
}

error_reporting(E_ALL);
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
ini_set('error_log', LOGS);

date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');

require ORIGIN . '/src/Exception/Exception.php';

/**
 * Load our own autoloader
 */
require ORIGIN . '/src/Core/Autoloader.php';
require CONFIG . '/autoload.php';
/**
 * Load the composer autoloader
 */
require ROOT . '/vendor/autoload.php';

/**
 * Error and Exception handling.
 */
if (PHP_SAPI === 'cli') {
    $ErrorHandler = new Origin\Console\ErrorHandler();
} else {
    $ErrorHandler = new Origin\Core\ErrorHandler();
}
$ErrorHandler->register();

require __DIR__ . '/functions.php';

/**
 * Load Config
 */
require CONFIG . '/bootstrap.php';
if (file_exists(CONFIG . '/server.php')) {
    require CONFIG . '/server.php';
}
if (file_exists(CONFIG . '/email.php')) {
    require CONFIG . '/email.php';
}
if (file_exists(CONFIG . '/database.php')) {
    require CONFIG . '/database.php';
}

require CONFIG . '/routes.php';
