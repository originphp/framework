<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

use Origin\Log\Log;
use Origin\Core\PhpFile;
use Origin\DotEnv\DotEnv;

define('START_TIME', microtime(true));

/**
 * Load Autoloader
 */
require ORIGIN . '/src/Core/Exception/Exception.php';
require ORIGIN . '/src/Core/Autoloader.php';
require ROOT . '/vendor/autoload.php';

/**
 * Register error handler
 */
$errorHandler = (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') ? new Origin\Console\ErrorHandler() : new Origin\Http\ErrorHandler();
$errorHandler->register();

/**
 * @todo this is temporary will be loaded through composer, test bootstrap maybe.
 */
require ORIGIN . '/src/Core/functions.php';
require ORIGIN . '/src/I18n/functions.php';

// configure default logging (required)
// errors during early bootstrap stage can be caught e.g. parse error in application.php
Log::config('default', [
    'engine' => 'File',
    'file' => LOGS . '/application.log'
]);

/**
 * As of version 2.6 .env.php is the cached version of .env. Prior
 * to this config was set manually .env.php
 */
$configFile = ROOT . '/config/.env.php';
if (file_exists($configFile)) {
    $result = include $configFile;
    foreach ($result as $key => $value) {
        $_ENV[$key] = $value;
    }
} elseif (file_exists(ROOT . '/config/.env')) {
    $vars = (new DotEnv())->load(ROOT. '/config');
    $header = [
        '# .env (cached version) - Do not edit, delete instead',
        '# Automatically generated ' . now(),
    ];
    if (env('APP_DEBUG') === false) {
        (new PhpFile())->write($configFile, $vars, ['short' => true,'before' => implode("\n", $header)]);
    }
}
