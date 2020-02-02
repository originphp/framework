<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */
declare(strict_types = 1);
namespace Origin\Core;

use Origin\Inflector\Inflector;
use Origin\Core\Exception\MissingPluginException;

/**
 * For each plugin you will need to setup config/routes.php
 * Router::add('/demo/:controller/:action/*', ['plugin'=>'Demo']);
 */

class Plugin
{
    /**
     * File where the composer plugins are stored.
     *
     * @var string
     */
    const COMPOSER_PLUGINS = ROOT . DS . 'vendor' . DS . 'originphp-plugins.json';

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
        if (file_exists(static::COMPOSER_PLUGINS)) {
            $composer = json_decode(file_get_contents(static::COMPOSER_PLUGINS), true);
            /**
             * [Commands] => vendor/originphp/commands
             * [Debug] => vendor/originphp/debug-plugin
             * [Generate] => vendor/originphp/generate
             * [UserAuthentication] => plugins/user_authentication
             */
            foreach ($composer as $plugin => $path) {
                static::load($plugin, ['path' => ROOT . '/' . $path, 'autoload' => false]);
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
     * @return void
     */
    public static function load(string $plugin, array $options = []) : void
    {
        $options += [
            'routes' => true,
            'bootstrap' => true,
            'path' => PLUGINS . DS . Inflector::underscored($plugin),
            'autoload' => true,
        ];
        if (! file_exists($options['path'])) {
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
     * @return bool
     */
    public static function unload(string $plugin) : bool
    {
        if (isset(static::$loaded[$plugin])) {
            unset(static::$loaded[$plugin]);

            return true;
        }

        return false;
    }

    /**
     * Sets up the autoloading of classes for the plugin
     *
     * @param string $plugin
     * @return void
     */
    protected static function autoload(string $plugin) : void
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
     * @return bool
     */
    public static function bootstrap(string $plugin) : bool
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
     * @return void
     */
    public static function loadRoutes() : void
    {
        foreach (static::$loaded as $plugin => $options) {
            static::routes($plugin);
        }
    }

    /**
     * Loads routes for plugin. Used in config/routes.php.
     *
     * @param string $plugin
     * @return boolean
     */
    public static function routes(string $plugin) : bool
    {
        $options = static::$loaded[$plugin];
        if ($options['routes']) {
            return static::include($options['path'] . DS . 'config' . DS . 'routes.php');
        }

        return false;
    }

    /**
    * Gets a path for a plugin
    *
    * @param string $plugin
    * @return string
    * @throws \Origin\Core\Exception\MissingPluginException
    */
    public static function path(string $plugin) : string
    {
        if (! isset(static::$loaded[$plugin])) {
            throw new MissingPluginException($plugin);
        }

        return static::$loaded[$plugin]['path'];
    }

    /**
     * Includes a file
     *
     * @param string $filename
     * @return bool
     */
    protected static function include(string $filename) : bool
    {
        if (file_exists($filename)) {
            return (bool) include $filename;
        }

        return false;
    }
}
