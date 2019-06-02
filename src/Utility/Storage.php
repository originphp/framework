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
            'className' => 'Origin\Engine\Storage\DiskEngine',
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
     * Gets the cache engine
     *
     * @param string $config
     * @return Origin\Engine\Storage\StorageEngine
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
            
            return static::$loaded[$name] = new $config['className']($config);
        }
        throw new InvalidArgumentException("{$config} config does not exist");
    }

    /**
     * Reads an item from the Cache
     *
     * @param string $name
     * @param string $config
     * @return mixed
     */
    public static function read(string $name, string $config ='default')
    {
        return static::engine($config)->read($name);
    }
    /**
     * Writes an item from Cache
     *
     * @param string $name
     * @param mixed $value
     * @param string $config
     * @return bool
     */
    public static function write(string $name, $value, $config='default'):bool
    {
        return static::engine($config)->write($name,$value);
    }

    /**
     * Checks if an item is in the cache
     *
     * @param string $name
     * @param mixed $value
     * @param string $config
     * @return bool
     */
    public static function exists(string $name, $config='default'):bool
    {
        return static::engine($config)->exists($name);
    }

    /**
     * Deletes an item from the cache
     *
     * @param string $name
     * @param string $config
     * @return bool
     */
    public static function delete(string $name, $config='default') :bool
    {
        return static::engine($config)->delete($name);
    }

    /**
     * Returns a list of items in the storage
     *
     * @param string $config images or public/images
     * @return array
     */
    public static function list(string $path = null,$config='default') :array
    {
        return static::engine($config)->list($path);
    }
  
}
