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

    protected static $disabled = false;

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
       
        $config = static::getConfig($name);
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
     * Reads an item from the Cache
     *
     * @param string $key
     * @param string $config
     * @return mixed
     */
    public static function read(string $key, string $config ='default')
    {
        $cache = static::engine($config);
        return $cache->get($key);
    }
    /**
     * Writes an item from Cache
     *
     * @param string $key
     * @param mixed $value
     * @param string $config
     * @return bool
     */
    public static function write(string $key, $value, $config='default'):bool
    {
        $cache = static::engine($config);
        return $cache->set($key, $value);
    }

    /**
     * Checks if an item is in the cache
     *
     * @param string $key
     * @param mixed $value
     * @param string $config
     * @return bool
     */
    public static function check(string $key, $config='default'):bool
    {
        $cache = static::engine($config);
        return $cache->has($key);
    }

    /**
     * Deletes an item from the cache
     *
     * @param string $key
     * @param string $config
     * @return bool
     */
    public static function delete(string $key, $config='default') :bool
    {
        $cache = static::engine($config);
        return $cache->delete($key);
    }
    /**
     * Clears the cache
     *
     * @return void
     */
    public static function clear($config='default') :bool
    {
        $cache = static::engine($config);
        return $cache->clear();
    }

    public static function disable()
    {
        static::$nullEngine = new NullEngine();
        static::$disabled =  true;
    }

    public static function enable()
    {
        static::$disabled =  false;
    }
}
