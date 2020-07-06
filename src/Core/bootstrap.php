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

use Origin\Core\PhpFile;
use Origin\DotEnv\DotEnv;

define('START_TIME', microtime(true));

/**
 * Load Autoloader
 */
require __DIR__ . '/Exception/Exception.php';
require __DIR__ . '/Autoloader.php';
require ROOT . '/vendor/autoload.php';

/**
 * Register framework error handler
 */
if ((PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg')) {
    ( new Origin\Console\ErrorHandler())->register();
} else {
    ( new Origin\Http\ErrorHandler())->register();
}

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
