<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2021 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright    Copyright (c) Jamiel Sharief
 * @link         https://www.originphp.com
 * @license      https://opensource.org/licenses/mit-license.php MIT License
 */
declare(strict_types=1);
namespace Origin\Redis;

use InvalidArgumentException;
use Origin\Configurable\StaticConfigurable as Configurable;

class Redis
{
    use Configurable;

    /**
     * Default configuration setup
     *
     * @var array
     */
    private static $defaultConfig = [
        'default' => [
            'host' => '127.0.0.1',
            'port' => 6379,
            'password' => null,
            'path' => null,
            'persistent' => false,
            'timeout' => 0,
            'database' => null,
            'prefix' => null
        ]
    ];

    /**
     * @var \Redis
     */
    private static $connection;

    /**
     * Gets a configured connection using settings from Redis::config()
     *
     * @param string $name
     * @return \Origin\Redis\Connection
     */
    public static function connection(string $name = 'default'): Connection
    {
        if (! static::config($name)) {
            throw new InvalidArgumentException(
                sprintf('The connection configuration "%s" does not exist.', $name)
            );
        }

        return new Connection(static::config($name));
    }

    /**
     * Sets a value in the default Redis connection
     *
     * @param string $key
     * @param mixed $value
     * @param array $options The following options keys are supported
     *  - duration: default:null number of seconds that value should be stored for
     * @return boolean
     */
    public static function set(string $key, $value, array $options = []): bool
    {
        return static::redis()->set($key, $value, $options);
    }

    /**
     * Gets a value from the default Redis connection
     *
     * @param string $key
     * @param mixed $default value to return if the key does not exist
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        return static::redis()->get($key, $default);
    }
    
    /**
    * Check if a key exists in the default Redis connection
    *
    * @param string $key
    * @return boolean
    */
    public static function exists(string $key): bool
    {
        return static::redis()->exists($key);
    }

    /**
     * Deletes a key from the default Redis connection
     *
     * @param string $key
     * @return boolean
     */
    public static function delete(string $key): bool
    {
        return static::redis()->delete($key);
    }

    /**
     * Increases a value in the default Redis connection
     *
     * @param string $key
     * @param integer $offset
     * @return integer $count
     */
    public static function increment(string $key, int $offset = 1): int
    {
        return static::redis()->increment($key, $offset);
    }

    /**
     * Decreases a value in the default Redis connection
     *
     * @param string $key
     * @param integer $offset
     * @return integer $count
     */
    public static function decrement(string $key, int $offset = 1): int
    {
        return static::redis()->decrement($key, $offset);
    }

    /**
     * Gets the list of keys in the default Redis connection
     *
     * @return array
     */
    public static function keys(): array
    {
        return static::redis()->keys();
    }

    /**
     * Removes all keys from the current database
     *
     * @return boolean
     */
    public static function flush(): bool
    {
        return static::redis()->flush();
    }

    /**
     * Gets PHP Redis extension client for the default connection
     *
     * @return \Redis
     */
    public static function client(array $options = []): \Redis
    {
        return static::redis();
    }

    private static function redis(array $options = []): Connection
    {
        if (! isset(static::$connection)) {
            static::$connection = static::connection($options['connection'] ?? 'default');
        }

        return static::$connection;
    }
}
