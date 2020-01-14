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
namespace Origin\Model;

use Origin\Core\Resolver;
use Origin\Core\Exception\Exception;

class ModelRegistry
{
    /**
     * Holds the object.
     *
     * @var array
     */
    protected static $registry = [];

    protected static $config = [];

    /**
     * Gets the model from the Registry. If the model is not in the registry
     * then it will create it and add it.
     *
     * @param string $alias
     * @param array  $options
     * @return \Origin\Model\Model|null
     */
    public static function get(string $alias, array $options = []) : ?Model
    {
        list($plugin, $model) = pluginSplit($alias);
        
        if (empty($options['className'])) {
            $options['className'] = $alias;
        }
        $options['alias'] = $model;
 
        if (isset(static::$registry[$model])) {
            return static::$registry[$model];
        }

        if (isset(static::$config[$model])) {
            $options += static::$config[$model];
        }
        
        /**
         * Set the datasource to test when in the test environment
         */
        if (env('ORIGIN_ENV') === 'test' and ! isset($options['connection'])) {
            $options['connection'] = 'test';
        }

        $object = static::create($model, $options);

        if ($object) {
            static::set($model, $object);

            return $object;
        }

        return null;
    }

    /**
     * Adds an object to the registry.
     *
     * @param string $key name of object
     * @param \Origin\Model\Model $object
     * @return void
     */
    public static function set(string $key = null, Model $object) : void
    {
        static::$registry[$key] = $object;
    }

    /**
     * Checks if an object is in the registry.
     *
     * @param string $key name of object
     */
    public static function has($key = null) : bool
    {
        return isset(static::$registry[$key]);
    }

    /**
     * Deletes an object from the registry.
     *
     * @param string $key name of object
     * @return bool true or false
     */
    public static function delete($key = null) : bool
    {
        if (isset(static::$registry[$key])) {
            unset(static::$registry[$key]);

            return true;
        }

        return false;
    }

    /**
     * Clears the registry and resets state.
     *
     * @return void
     */
    public static function clear() : void
    {
        static::$config = static::$registry = [];
    }

    /**
     * Undocumented function
     *
     * @param string $className
     * @param array $options
     * @return \Origin\Model\Model|null
     */
    protected static function create(string $className, array $options) : ?Model
    {
        if (isset($options['className'])) {
            $className = $options['className'];
        }

        $className = Resolver::className($className, 'Model');

        if ($className) {
            return new $className($options);
        }

        return null;
    }

    /**
     * Stores config for a model. To get all data, dont set alias. To get
     * config for an alias dont supply config,.
     *
     * @param string $alias  model alias
     * @param array $config
     * @return array|null
     */
    public static function config(string $alias = null, array $config = null) : ?array
    {
        if ($alias === null) {
            return static::$config;
        }

        if ($config === null) {
            if (isset(static::$config[$alias])) {
                return static::$config[$alias];
            }

            return null;
        }
        if (isset(static::$registry[$alias])) {
            throw new Exception(sprintf('You cannot set the config for "%s" as it is  already in the registry', $alias));
        }

        return static::$config[$alias] = $config;
    }
}
