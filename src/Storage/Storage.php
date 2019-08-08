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

namespace Origin\Storage;

use Origin\Core\StaticConfigTrait;
use Origin\Storage\Engine\BaseEngine;
use Origin\Exception\InvalidArgumentException;

class Storage
{
    use StaticConfigTrait;

    protected static $defaultConfig = [
        'default' => [
            'className' => 'Origin\Storage\Engine\LocalEngine',
            'path' => APP . DS . 'storage',
        ],
    ];

    /**
     * Holds the Storage Engines
     *
     * @var array
     */
    protected static $loaded = [];

    /**
     * The default storage to use
     * @internal whilst use is being deprecated
     * @var string
     */
    protected static $default = 'default';

    /**
     * Alias for Storage::engine. Gets the configured engine
     *
     * @param string $config
     * @return \Origin\Storage\Engine\BaseEngine
     */
    public static function volume(string $name) : BaseEngine
    {
        return static::engine($name);
    }

    /**
     * Gets the configured Storage Engine
     *
     * @param string $config
     * @return \Origin\Storage\Engine\BaseEngine
     */
    public static function engine(string $name) : BaseEngine
    {
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
     * @return \Origin\Storage\Engine\BaseEngine
     */
    protected static function buildEngine(string $name) : BaseEngine
    {
        $config = static::config($name);
        if ($config) {
            if (isset($config['engine'])) {
                $config['className'] = "Origin\Storage\Engine\\{$config['engine']}Engine";
            }
            if (empty($config['className']) or ! class_exists($config['className'])) {
                throw new InvalidArgumentException("Storage Engine for {$name} could not be found");
            }

            return new $config['className']($config);
        }
        throw new InvalidArgumentException("{$config} config does not exist");
    }

    /**
     * Changes the storage config that is being used. Use this when working with multiple.
     * REMEMBER: to set even for default, if using in the same script.
     * @codeCoverageIgnore
     * @param string $config
     * @return void
     */
    public static function use(string $config)
    {
        deprecationWarning('Storage::use is deprecated use Storage::volume() or pass options.');
        if (! static::config($config)) {
            throw new InvalidArgumentException("{$config} config does not exist");
        }
        self::$default = $config;
    }

    /**
     * Reads an item from the Storage
     *
     * @param string $name
     * @param array $options You can pass an array of options with the folling keys :
     *   - config: default:default the name of the config to use
     * @return string
     */
    public static function read(string $name, array $options = []) : string
    {
        $options += ['config' => self::$default];
        $engine = static::engine($options['config']);

        return $engine->read($name);
    }
    /**
     * Writes an item from Storage
     *
     * @param string $name
     * @param mixed $value
     * @param array $options You can pass an array of options with the folling keys :
     *   - config: default:default the name of the config to use
     * @return bool
     */
    public static function write(string $name, $value, array $options = []) : bool
    {
        $options += ['config' => self::$default];
        $engine = static::engine($options['config']);

        return $engine->write($name, $value);
    }

    /**
     * Checks if an item is in the Storage
     *
     * @param string $name
     * @param mixed $value
     * @param array $options You can pass an array of options with the folling keys :
     *   - config: default:default the name of the config to use
     * @return bool
     */
    public static function exists(string $name, array $options = []) : bool
    {
        $options += ['config' => self::$default];
        $engine = static::engine($options['config']);

        return $engine->exists($name);
    }

    /**
     * Deletes a file OR directory
     *
     * @param string $name
     * @param array $options You can pass an array of options with the folling keys :
     *   - config: default:default the name of the config to use
     * @return boolean
     */
    public static function delete(string $name, array $options = []) : bool
    {
        $options += ['config' => self::$default];
        $engine = static::engine($options['config']);

        return $engine->delete($name);
    }

    /**
     * Returns a list of items in the storage
     *
     * @param string $path images or public/images
     * @param array $options You can pass an array of options with the folling keys :
     *   - config: default:default the name of the config to use
     * @return array
     */
    public static function list(string $path = null, array $options = []) : array
    {
        $options += ['config' => self::$default];
        $engine = static::engine($options['config']);

        return $engine->list($path);
    }
}
