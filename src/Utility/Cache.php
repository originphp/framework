<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright     Copyright (c) Jamiel Sharief
 * @link         https://www.originphp.com
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Utility;

use Origin\Core\StaticConfigTrait;
use Origin\Exception\InvalidArgumentException;
use Origin\Engine\Cache\NullEngine;

class Cache
{
    use StaticConfigTrait;
    protected static $defaultConfig = [
        'default' => [
            'className' => 'Origin\Engine\Cache\FileEngine',
            'duration' => 3600
            ]
    ];

    /**
     * Holds the cache engines
     *
     * @var array
     */
    protected static $loaded = [];

    /**
     * Holds the enabled/disabled bool
     *
     * @var boolean
     */
    protected static $disabled = false;

    /**
      * Which storage config is being used
      *
      * @var string
      */
    protected static $use = 'default';

    /**
     * Undocumented variable
     *
     * @var \Origin\Engine\Cache\NullEngine
     */
    protected static $nullEngine = null;

    /**
     * Gets the configfured cache engine
     *
     * @param string $config
     * @return \Origin\Engine\CacheEngine
     */
    public static function engine(string $name)
    {
        if (static::$disabled) {
            return static::$nullEngine;
        }

        if (isset(static::$loaded[$name])) {
            return static::$loaded[$name];
        }
       
        $config = static::config($name);
        if ($config) {
            if (isset($config['engine'])) {
                $config['className'] = "Origin\Engine\Cache\\{$config['engine']}Engine";
            }
            
            if (empty($config['className'])) {
                throw new InvalidArgumentException("Cache engine for {$name} could not be found");
            }
            return static::$loaded[$name] = new $config['className']($config);
        }
        throw new InvalidArgumentException("{$config} config does not exist");
    }
    
    /**
    * Changes the cache config that is being used. Use this when working with multiple cache configurations.
    * REMEMBER: to even set for default when working with multiple configurations.
    *
    * @param string $config
    * @return void
    */
    public static function use(string $config)
    {
        if (!static::config($config)) {
            throw new InvalidArgumentException("{$config} config does not exist");
        }
        self::$use = $config;
    }


    /**
     * Reads an item from the Cache
     *
     * @param string $key
     * @return mixed
     */
    public static function read(string $key)
    {
        $cache = static::engine(self::$use);
        return $cache->read($key);
    }
    /**
     * Writes an item from Cache
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public static function write(string $key, $value):bool
    {
        $cache = static::engine(self::$use);
        return $cache->write($key, $value);
    }

    /**
     * Checks if an item is in the cache
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public static function check(string $key):bool
    {
        deprecationWarning('Cache::check is depreciated use cache::exists');
        return static::exists($key);
    }

    /**
    * Checks if an item is in the cache
    *
    * @param string $key
    * @param mixed $value
    * @return bool
    */
    public static function exists(string $key):bool
    {
        $cache = static::engine(self::$use);
        return $cache->exists($key);
    }

    /**
     * Deletes an item from the cache
     *
     * @param string $key
     * @return bool
     */
    public static function delete(string $key, $config='default') :bool
    {
        $cache = static::engine(self::$use);
        return $cache->delete($key);
    }
    /**
     * Clears the cache
     *
     * @return void
     */
    public static function clear() :bool
    {
        $cache = static::engine(self::$use);
        return $cache->clear();
    }

    /**
     * Disables the cache
     *
     * @return void
     */
    public static function disable()
    {
        static::$nullEngine = new NullEngine();
        static::$disabled =  true;
    }
    
    /**
     * Enables the Cache
     *
     * @return void
     */
    public static function enable()
    {
        static::$nullEngine = null;
        static::$disabled =  false;
    }
}
