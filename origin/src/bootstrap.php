<?php
/**
 * OriginPHP Framework
 * Copyright 2018 Jamiel Sharief.
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
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(dirname(dirname(__FILE__))));
define('ORIGIN', dirname(dirname(__FILE__)));
define('CONFIG', ROOT.DS.'config');
define('SRC', ROOT.DS.'src');
define('TMP', ROOT.DS.'tmp');
define('LOGS', ROOT.DS.'logs');

define('WEBROOT', ROOT.DS.'webroot');
define('VIEW', SRC.DS.'View');

define('SESSIONS', TMP.DS.'sessions');
define('PLUGINS', ROOT.DS.'plugins');

ini_set('error_log', LOGS);

date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');

require ORIGIN.DS.'src'.DS.'Exception'.DS.'Exception.php';

/**
 * Load our own autoloader
 */
require ORIGIN . DS . 'src' . DS .'Core' .DS .'Autoloader.php';
require CONFIG.DS.'autoload.php';
/**
 * Load the composer autoloader
 */
require ROOT.'/vendor/autoload.php';

/**
 * Error and Exception handling.
 */
if (php_sapi_name() === 'cli') {
    $ErrorHandler = new Origin\Console\ErrorHandler();
} else {
    $ErrorHandler = new Origin\Core\ErrorHandler();
}
$ErrorHandler->register();

require 'functions.php';
/**
 * Load Config
 */
require CONFIG.DS.'bootstrap.php';
if (file_exists(CONFIG.DS.'server.php')) {
    require CONFIG.DS.'server.php';
}
if (file_exists(CONFIG.DS.'email.php')) {
    require CONFIG.DS.'email.php';
}
if (file_exists(CONFIG.DS.'database.php')) {
    require CONFIG.DS.'database.php';
}

use Origin\Core\Session;

Session::init();
require CONFIG.DS.'routes.php';

if ($_GET) {
    $_GET = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);
}
if ($_POST) {
    $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
}
