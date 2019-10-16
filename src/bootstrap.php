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

define('START_TIME', microtime(true));

error_reporting(E_ALL);
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
ini_set('error_log', LOGS);

date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');

require ORIGIN . '/src/Exception/Exception.php';
require ORIGIN . '/src/Core/Autoloader.php';
require ROOT . '/vendor/autoload.php';

$errorHandler = (PHP_SAPI === 'cli') ? new Origin\Console\ErrorHandler() : new Origin\Http\ErrorHandler();
$errorHandler->register();

require __DIR__ . '/functions.php';

if (file_exists(CONFIG . DS . '.env.php')) {
    $result = include CONFIG . DS . '.env.php';
    foreach ($result as $key => $value) {
        $_ENV[$key] = $value;
    }
}
