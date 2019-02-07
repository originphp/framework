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
     * Checks if a plugin is loaded or returns a list of loaded plugins
     *
     * @param string|null $plugin
     * @return bool|array
     */
    public static function loaded(string $plugin = null)
    {
        if ($plugin) {
            return isset(static::$loaded[$plugin]);
        }
        return array_keys(static::$loaded);
    }

    public static function load(string $plugin, array $options = array())
    {
        $defaults = array('routes' => true, 'bootstrap' => true);
        $options = array_merge($defaults, $options);
        $options['path'] = PLUGINS . DS . Inflector::underscore($plugin) . DS . 'src';
    
        if (!file_exists($options['path'])) {
            throw new MissingPluginException($plugin);
        }
        static::$loaded[$plugin] = $options;

        static::bootstrap($plugin);
 
        static::autoloader()->addNamespaces(array(
          $plugin => 'plugins/'.Inflector::underscore($plugin).'/src',
          "{$plugin}\\Test" => 'plugins/'.Inflector::underscore($plugin).'/tests',
        ));
    }


    protected static function autoloader()
    {
        /**
             * Create Autoloader object for plugins
             */
        if (empty(static::$autoloader)) {
            static::$autoloader = Autoloader::init();
        }
        return static::$autoloader;
    }
    public static function bootstrap(string $plugin = null)
    {
        if ($plugin === null) {
            foreach (static::$loaded as $plugin => $options) {
                static::bootstrap($plugin);
            }
            return true;
        }
        $options = static::$loaded[$plugin];
        if ($options['bootstrap']) {
            return static::include($options['path'].DS.'config'.DS.'bootstrap.php');
        }
        return false;
    }
    /**
     * Loads routes for plugin or all plugins. Used in config/routes.php.
     */
    public static function routes(string $plugin = null)
    {
        if ($plugin === null) {
            foreach (static::$loaded as $plugin => $options) {
                static::routes($plugin);
            }
            return true;
        }
        $options = static::$loaded[$plugin];
        if ($options['routes']) {
            return static::include($options['path'].DS.'config'.DS.'routes.php');
        }
        return false;
    }

    protected static function include(string $filename)
    {
        if (file_exists($filename)) {
            return include $filename;
        }
        return false;
    }
}
