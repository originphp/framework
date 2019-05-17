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
 * @see         https://www.originphp.com
 * @license      https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Console;
require dirname(__DIR__).'/bootstrap.php';


use Origin\Console\CommandRunner;
$commandRunner = new CommandRunner();
$commandRunner->run($argv);

/*
require dirname(__DIR__).'/bootstrap.php';
use Origin\Console\ConsoleApplication;

$consoleApplication = new ConsoleApplication();
$consoleApplication->loadCommand('Dev');
$consoleApplication->loadCommand('App\Command\App\CreateTableCommand');
$consoleApplication->run();
*/