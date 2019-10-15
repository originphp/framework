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

error_reporting(E_ALL);
ini_set('display_errors', true);

define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__DIR__));
define('ORIGIN', ROOT);

define('APP', ORIGIN . DS . 'tests' . DS . 'TestApp' . DS . 'app');
define('CONFIG', ORIGIN . DS . 'tests' . DS . 'TestApp' . DS . 'config');
define('DATABASE', ORIGIN . DS . 'tests' . DS . 'TestApp' . DS . 'database');
define('PLUGINS', ORIGIN . DS . 'tests' . DS . 'TestApp' . DS . 'plugins');
define('TESTS', ORIGIN . DS . 'tests' . DS . 'TestApp' . DS . 'tests');
define('WEBROOT', ROOT . DS . 'public');

define('TMP', sys_get_temp_dir());
define('LOGS', TMP . DS . 'logs');
define('CACHE', TMP . DS . 'cache');

@mkdir(LOGS);
@mkdir(CACHE);

require ORIGIN . DS . 'src' . DS . 'bootstrap.php';
