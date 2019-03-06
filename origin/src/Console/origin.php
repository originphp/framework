<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright    Copyright (c) Jamiel Sharief
 * @link         https://www.originphp.com
 * @license      https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Origin\Console;

use Origin\Console\ShellDispatcher;
use Origin\Console\ConsoleOutput;

define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(dirname(dirname(dirname(__FILE__)))));

define('CONFIG', ROOT . DS . 'config');
define('LOGS', ROOT . DS . 'logs');
define('ORIGIN', ROOT . DS . 'origin');
define('PLUGINS', ROOT . DS . 'plugins');
define('SRC', ROOT . DS . 'src');
define('TMP', ROOT . DS . 'tmp');
define('WEBROOT', ROOT . DS . 'webroot');

require  'origin/src/bootstrap.php';

$Dispatcher = new ShellDispatcher($argv, new ConsoleOutput());
$Dispatcher->start();
