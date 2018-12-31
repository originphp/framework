<?php
/**
 * OriginPHP Framework
 * Copyright 2018 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright     Copyright (c) Jamiel Sharief
 *
 * @link          https://www.originphp.com
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Core;

class Plugin
{
    protected static $loaded = array();

    protected static $autoloader = null;

    public static function load(string $plugin, array $options = array())
    {
        $defaults = array('routes' => true, 'bootstrap' => true);
        $options = array_merge($defaults, $options);
        $options['path'] = PLUGINS.DS.$plugin;

        $bootstrapFilename = $options['path'].DS.'config'.DS.'bootstrap.php';
        if ($options['bootstrap'] and file_exists($bootstrapFilename)) {
            include $bootstrapFilename;
        }

        if (!file_exists($options['path'])) {
            throw new MissingPluginException($plugin);
        }
        if (empty(self::$autoloader)) {
            self::$autoloader = new Autoloader(ROOT);
            self::$autoloader->register();
        }

        self::$autoloader->addNamespaces(array(
          $plugin => 'plugins/'.Inflector::underscore($plugin).'/src',
           "{$plugin}\\Test" => 'plugins/'.Inflector::underscore($plugin).'/tests',
    ));

        self::$loaded[$plugin] = $options;
    }

    /**
     * Loads routes for all plugins. Used by config/routes.php.
     */
    public static function loadRoutes()
    {
        foreach (self::$loaded as $plugin) {
            $routesFilename = $plugin['path'].DS.'config'.DS.'routes.php';
            if ($plugin['routes'] and file_exists($routesFilename)) {
                include $routesFilename;
            }
        }
    }
}
