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

namespace Origin\Core;

use Origin\Core\Exception\MissingPluginException;

/**
 * For each plugin you will need to setup config/routes.php
 * Router::add('/demo/:controller/:action/*', ['plugin'=>'Demo']);
 */

class Plugin
{
    /**
     * Loaded plugins are stored here
     *
     * @var array
     */
    protected static $loaded = array();

    /**
     * Holds an Autoloader object
     *
     * @var Autoloader
     */
    protected static $autoloader = null;

    /**
     * Checks if a plugin is loaded or returns a list of all loaded plugin
     *
     * @param string|null $plugin
     * @return bool|array
     */
    public static function loaded(string $plugin = null)
    {
        if ($plugin) {
            return static::$loaded[$plugin];
        }
        $loaded = array_keys(static::$loaded);
        sort($loaded);
        return $loaded;
    }

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
      
        /**
         * Create Autoloader object for plugins
         */
        if (empty(static::$autoloader)) {
            static::$autoloader = Autoloader::init();
        }
      
        static::$autoloader->addNamespaces(array(
          $plugin => 'plugins/'.Inflector::underscore($plugin).'/src',
           "{$plugin}\\Test" => 'plugins/'.Inflector::underscore($plugin).'/tests',
        ));
        
        static::$loaded[$plugin] = $options;
    }

    /**
     * Loads routes for all plugins. Used by config/routes.php.
     */
    public static function loadRoutes()
    {
        foreach (static::$loaded as $plugin) {
            $routesFilename = $plugin['path'].DS.'config'.DS.'routes.php';
            if ($plugin['routes'] and file_exists($routesFilename)) {
                include $routesFilename;
            }
        }
    }
}
