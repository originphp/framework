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
use Origin\Cache\Cache;
use Origin\Core\Config;
use Origin\Core\PhpFile;
use Origin\DotEnv\DotEnv;
use Origin\Core\Autoloader;

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
$errorHandler = (PHP_SAPI === 'cli' or PHP_SAPI === 'phpdbg') ? new Origin\Console\ErrorHandler() : new Origin\Http\ErrorHandler();
$errorHandler->register();

require ORIGIN . '/src/Core/functions.php';

// configure default logging (required)
// errors during early bootstrap stage can be caught e.g. parse error in application.php
Log::config('default', [
    'engine' => 'File',
    'file' => LOGS . '/application.log'
]);

// internal caching
/**
 * Backwards comptability
 */
Cache::config('origin', [
    'engine' => 'File',
    'path' => CACHE . '/origin',
    'duration' => debugEnabled() ? '+2 minutes' : '+24 hours',
    'prefix' => 'cache_',
    'serialize' => true
]);

/**
 * As version 2.5 .env.php is the cached version of .env. Prior
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
    (new PhpFile())->write($configFile, $vars, ['short' => true,'before' => implode("\n", $header)]);
}

/**
 * Moved here from bootstrap in version 2.5
 */
$autoloader = Autoloader::instance();
$autoloader->directory(ROOT);

$autoloader->addNamespaces([
    'App' => 'app',
    'App\\Test' => 'tests'
]);
$autoloader->register();
