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
 * Here you can configure your routes, the order which they are added is important.
 * The first match route will be used.
 */
use Origin\Core\Plugin;
use Origin\Http\Router;

/*
* Add your routes here
* @example
* Router::add('/login',['controller'=>'Users','action'=>'login']);
*/

Router::add('/', ['controller' => 'Pages', 'action' => 'display', 'home']);
Router::add('/pages/*', ['controller' => 'Pages','action' => 'display']);

/*
* Load the routes for plugins
*/
Plugin::loadRoutes();

/*
* Load default routes - You can remove these if you want but you will need to add a
* route for each controller/action etc.
*/
Router::add('/:controller/:action/*');
Router::add('/:controller', ['action' => 'index']);
