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

namespace Origin\Cache;

use Origin\Core\StaticConfigTrait;
use Origin\Cache\Engine\BaseEngine;
use Origin\Cache\Engine\NullEngine;
use Origin\Exception\InvalidArgumentException;

class Cache
{
    use StaticConfigTrait;
    protected static $defaultConfig = [
        'default' => [
            'className' => __NAMESPACE__ . '\Engine\FileEngine',
            'duration' => 3600,
        ],
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
      * The default cache storage to use
      *
      * @var string
      */
    protected static $default = 'default';

    /**
     * Undocumented variable
     *
     * @var \Origin\Cache\Engine\NullEngine
     */
    protected static $nullEngine = null;

    /**
     * Alias for Cache::engine. Gets the configured engine
     *
     * @param string $config
     * @return \Origin\Cache\Engine\BaseEngine
     */
    public static function store(string $name)
    {
        return static::engine($name);
    }

    /**
     * Gets the configured Cache Engine
     *
     * @param string $config
     * @return \Origin\Cache\Engine\BaseEngine
     */
    public static function engine(string $name)
    {
        if (static::$disabled) {
            return static::$nullEngine;
        }

        if (isset(static::$loaded[$name])) {
            return static::$loaded[$name];
        }

        return static::$loaded[$name] = static::buildEngine($name);
    }

    /**
     * Builds an engine using the configuration
     *
     * @param string $name
     * @throws \Origin\Exception\InvalidArgumentException
     * @return \Origin\Cache\Engine\BaseEngine
     */
    protected static function buildEngine(string $name) : BaseEngine
    {
        $config = static::config($name);
        if ($config) {
            if (isset($config['engine'])) {
                $config['className'] = __NAMESPACE__  . "\Engine\\{$config['engine']}Engine";
            }
            if (empty($config['className']) or ! class_exists($config['className'])) {
                throw new InvalidArgumentException("Cache engine for {$name} could not be found");
            }

            return new $config['className']($config);
        }
        throw new InvalidArgumentException(sprintf('The cache configuration `%s` does not exist.', $name));
    }
    
    /**
     * Reads an item from the Cache
     *
     * @param string $key name of the key
     * @param array $options You can pass an array of options with the folling keys :
     *   - config: default:default the name of the config to use
     * @return mixed
     */
    public static function read(string $key, array $options = [])
    {
        $options += ['config' => self::$default]; // be comptabile with use whilst its deprecated
        $cache = static::engine($options['config']);

        return $cache->read($key);
    }
    /**
     * Writes an item from Cache
     *
     * @param string $key
     * @param mixed $value
     * @param array $options You can pass an array of options with the folling keys :
     *   - config: default:default the name of the config to use
     * @return bool
     */
    public static function write(string $key, $value, array $options = []):bool
    {
        $options += ['config' => self::$default]; // be comptabile with use whilst its deprecated
        $cache = static::engine($options['config']);

        return $cache->write($key, $value);
    }

    /**
    * Checks if an item is in the cache
    *
    * @param string $key
    * @param array $options You can pass an array of options with the folling keys :
    *   - config: default:default the name of the config to use
    * @return bool
    */
    public static function exists(string $key, array $options = []):bool
    {
        $options += ['config' => self::$default]; // be comptabile with use whilst its deprecated
        $cache = static::engine($options['config']);

        return $cache->exists($key);
    }

    /**
     * Deletes an item from the cache
     *
     * @param string $key
     * @param array $options You can pass an array of options with the folling keys :
     *   - config: default:default the name of the config to use
     * @return bool
     */
    public static function delete(string $key, array $options = []) :bool
    {
        $options += ['config' => self::$default]; // be comptabile with use whilst its deprecated
        $cache = static::engine($options['config']);

        return $cache->delete($key);
    }

    /**
     * Increments a value in the cache
     *
     * @param string $key
     * @param integer $offset
     * @param array $options You can pass an array of options with the folling keys :
     *   - config: default:default the name of the config to use
     * @return integer
     */
    public static function increment(string $key, int $offset = 1, array $options = []): int
    {
        $options += ['config' => self::$default]; // be comptabile with use whilst its deprecated
        $cache = static::engine($options['config']);

        return $cache->increment($key, $offset);
    }

    /**
     * Decreases a value a value in the cache
     *
     * @param string $key
     * @param integer $offset
     * @param array $options You can pass an array of options with the folling keys :
     *   - config: default:default the name of the config to use
     * @return integer
     */
    public static function decrement(string $key, int $offset = 1, array $options = []): int
    {
        $options += ['config' => self::$default]; // be comptabile with use whilst its deprecated
        $cache = static::engine($options['config']);

        return $cache->decrement($key, $offset);
    }

    /**
     * Clears the cache
     * @param array $options You can pass an array of options with the folling keys :
     *   - config: default:default the name of the config to use
     * @return void
     */
    public static function clear(array $options = []) :bool
    {
        $options += ['config' => self::$default]; // be comptabile with use whilst its deprecated
        $cache = static::engine($options['config']);

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
        static::$disabled = true;
    }
    
    /**
     * Enables the Cache
     *
     * @return void
     */
    public static function enable()
    {
        static::$nullEngine = null;
        static::$disabled = false;
    }
}
