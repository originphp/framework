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

class Storage
{
    use StaticConfigTrait;

    protected static $defaultConfig = [
        'default' => [
            'className' => 'Origin\Engine\Storage\LocalEngine',
            'path' => APP . DS . 'storage'
        ]
    ];

    /**
     * Holds the cache engines
     *
     * @var array
     */
    protected static $loaded = [];

    /**
     * Which storage config is being used
     *
     * @var string
     */
    protected static $use = 'default';
    
    /**
     * Gets the  configured storage engine
     *
     * @param string $config
     * @return \Origin\Engine\StorageEngine
     */
    public static function engine(string $name)
    {
        if (isset(static::$loaded[$name])) {
            return static::$loaded[$name];
        }
       
        $config = static::getConfig($name);
        if ($config) {
            if (isset($config['engine'])) {
                $config['className'] = "Origin\Engine\Storage\\{$config['engine']}Engine";
            }
            if (empty($config['className'])) {
                throw new InvalidArgumentException("Storage engine for {$name} could not be found");
            }
            return static::$loaded[$name] = new $config['className']($config);
        }
        throw new InvalidArgumentException("{$config} config does not exist");
    }

    /**
     * Changes the storage config that is being used. Use this when working with multiple.
     * REMEMBER: to set even for default, if using in the same script.
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
     * @param string $name
     * @param string $config
     * @return mixed
     */
    public static function read(string $name)
    {
        return static::engine(self::$use)->read($name);
    }
    /**
     * Writes an item from Cache
     *
     * @param string $name
     * @param mixed $value
     * @param string $config
     * @return bool
     */
    public static function write(string $name, $value):bool
    {
        return static::engine(self::$use)->write($name, $value);
    }

    /**
     * Checks if an item is in the cache
     *
     * @param string $name
     * @param mixed $value
     * @param string $config
     * @return bool
     */
    public static function exists(string $name):bool
    {
        return static::engine(self::$use)->exists($name);
    }

    /**
     * Deletes an item from the cache
     *
     * @param string $name
     * @param string $config
     * @return bool
     */
    public static function delete(string $name) :bool
    {
        return static::engine(self::$use)->delete($name);
    }

    /**
     * Returns a list of items in the storage
     *
     * @param string $config images or public/images
     * @return array
     */
    public static function list(string $path = null) :array
    {
        return static::engine(self::$use)->list($path);
    }
}
