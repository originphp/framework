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
 * This is the autoloader configuration file it is responsible for setting up namespaces and directories
 * so that autoloader can load classes. If you have non composer packages that you want to install then you
 * can configure it here, if you have composer packages then use the composer.json file.
 */

use Origin\Core\Autoloader;

$Autoloader = Autoloader::init();
$Autoloader->setFolder(ROOT);

$Autoloader->addNamespaces([
    'App' => 'src',
    'Origin' => 'origin/src',
    'Origin\\Test' => 'origin/tests'
]);

$Autoloader->register();
