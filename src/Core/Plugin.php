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

use Origin\Core\Autoloader;
use Origin\Core\Exception\MissingPluginException;

/**
 * For each plugin you will need to setup config/routes.php
 * Router::add('/demo/:controller/:action/*', ['plugin'=>'Demo']);
 */

class Plugin
{
    /**
     * Loaded plugins are stored here with config
     *
     * @var array
     */
    protected static $loaded = [];


    /**
     * Initializes the Plugin Object
     * Will load the plugins installed by composer
     */
    public static function initialize()
    {
        $composerPlugins = ROOT . DS . 'vendor' . DS . 'originphp-plugins.json';
        if (file_exists($composerPlugins)) {
            $composer = json_decode(file_get_contents($composerPlugins), true);
            foreach ($composer as $plugin => $path) {
                static::load($plugin, [
                    'path' => ROOT . $path, // Put to verify exists
                    'autoload'=>false
                    ]);
            }
        }
    }

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


    /**
     * Loads a plugin
     *
     * @param string $plugin
     * @param array $options
     */
    public static function load(string $plugin, array $options = [])
    {
        $options += [
            'routes' => true,
            'bootstrap' => true,
            'path' => PLUGINS . DS . Inflector::underscore($plugin),
            'autoload' => true,
        ];
        if (!file_exists($options['path'])) {
            throw new MissingPluginException($plugin);
        }

        static::$loaded[$plugin] = $options;

        static::bootstrap($plugin);

        if ($options['autoload']) {
            static::autoload($plugin);
        }
    }

    /**
     * Unloads a plugin for use
     *
     * @param string $plugin
     * @return void
     */
    public static function unload(string $plugin)
    {
        if (isset(static::$loaded[$plugin])) {
            unset(static::$loaded[$plugin]);
            return true;
        }
        return false;
    }

    /**
     * Sets up the autoloading of classes for the plugin
     */
    protected static function autoload(string $plugin)
    {
        $autoloader = Autoloader::instance();
        $options = static::$loaded[$plugin];
        $pluginPath = str_replace(ROOT .DS, '', $options['path']);
        $autoloader->addNamespaces([
            $plugin => $pluginPath . '/src',
            "{$plugin}\\Test" => $pluginPath . '/tests',
        ]);
    }
    
    /**
     * Loads the bootstrap config for a plugin
     *
     * @param string $plugin
     * @return void
     */
    public static function bootstrap(string $plugin)
    {
        $options = static::$loaded[$plugin];
        if ($options['bootstrap']) {
            return static::include($options['path'] . DS . 'config' . DS . 'bootstrap.php');
        }
        return false;
    }

    /**
     * Loads  the routes for all the plugins
     *
     * @param string $plugin
     * @return void
     */
    public static function loadRoutes()
    {
        foreach (static::$loaded as $plugin => $options) {
            static::routes($plugin);
        }
        return true;
    }
    /**
     * Loads routes for plugin. Used in config/routes.php.
     */
    public static function routes(string $plugin)
    {
        $options = static::$loaded[$plugin];
        if ($options['routes']) {
            return static::include($options['path'] . DS . 'config' . DS . 'routes.php');
        }
        return false;
    }

    /**
     * Includes a file
     *
     * @param string $filename
     * @return void
     */
    protected static function include(string $filename)
    {
        if (file_exists($filename)) {
            return (bool) include $filename;
        }
        return false;
    }
}
