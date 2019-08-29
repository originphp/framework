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
use Origin\Cache\Cache;
use Origin\Core\Configure;

/**
 * FrontController.
 */

/**
 * Load the Paths constants, if not already set (e.g. Tests)
 */

$legacy = false;

if (! defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);

    define('ROOT', dirname(dirname(dirname(dirname(__DIR__)))));
    define('CONFIG', ROOT . DS . 'config');
    define('LOGS', ROOT . DS . 'logs');
    define('ORIGIN', ROOT . DS . 'vendor'. DS . 'originphp'. DS . 'framework');
    define('PLUGINS', ROOT . DS . 'plugins');

    /**
     * @deprecated Added notice here so it can be removed in v2
     * Going back to app folder it was easier to work with
     * DATBASE_FOLDER/MIGRATIONS_FOLDER are to help with this and all references will be
     * removed in v2
     */
    $legacy = file_exists(ROOT . DS . 'src');
    if ($legacy) {
        define('SRC', ROOT . DS . 'src');
        define('DATABASE_FOLDER', 'db');
        define('MIGRATIONS_FOLDER', 'migrate');
    } else {
        define('SRC', ROOT . DS . 'app');
        define('DATABASE_FOLDER', 'database');
        define('MIGRATIONS_FOLDER', 'migrations');
    }
    
    define('APP', ROOT);
    define('TESTS', ROOT . DS . 'tests');
    define('TMP', ROOT . DS . 'tmp');
    define('WEBROOT', ROOT . DS . 'public');
} else {
    $legacy = file_exists(ROOT . DS . 'src'); # work with tests
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

if (file_exists(CONFIG . DS . '.env')) {
    $dotEnv = new Origin\Core\DotEnv();
    $dotEnv->load(CONFIG . DS . '.env');
}

/**
 * Load Config
 */
require CONFIG . DS . 'bootstrap.php';

if ($legacy) {
    /**
     * @deprecated remove in v2
     */
    foreach (['server','database','email','storage'] as $config) {
        if (file_exists(CONFIG . DS .  $config . '.php')) {
            include CONFIG . DS .  $config . '.php';
        }
    }
} else {
    include CONFIG . DS .  'log.php';
    include CONFIG . DS .  'cache.php';
    include CONFIG . DS .  'database.php';
    include CONFIG . DS .  'storage.php';
    include CONFIG . DS .  'email.php';
    include CONFIG . DS .  'queue.php';
}

require CONFIG . DS . 'routes.php';

/**
 * Backwards comptability for projects created < 1.26
 * @todo this will be deprecated in 2.0
 */
if (! Cache::config('origin_model')) {
    Cache::config('origin_model', [
        'engine' => 'File',
        'prefix' => 'origin_model_',
        'duration' => '+5 minutes', // min 2 minutes
    ]);
}
if (! Configure::exists('Schema.format')) {
    Configure::write('Schema.format', 'php');
}
