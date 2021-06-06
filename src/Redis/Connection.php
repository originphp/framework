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

use \Redis;
use RuntimeException;

class Connection
{
    /**
     * Redis client
     *
     * @var \Redis
     */
    private $client;

    /**
     * Constructor
     *
     * @param array $config The following configuration keys are supported
     *  - host: default:127.0.0.1 redis host address
     *  - port: default: 6379 the port number
     *  - path: socket path
     *  - persistent: default:false wether to have a persistent connection
     *  - timeout: default:0
     *  - prefix: default:null prefix to use
     *  - database: default:null database number to use
     */
    public function __construct(array $config = [])
    {
        $config += [
            'host' => '127.0.0.1',
            'port' => 6379,
            'password' => null,
            'path' => null,
            'persistent' => false,
            'timeout' => 0,
            'database' => null,
            'prefix' => null
        ];

        $this->client = static::buildClient($config);
    }

    /**
     * Sets a value
     *
     * @param string $key
     * @param mixed $value
     * @param array $options The following options keys are supported
     *  - duration: default:null number of seconds that value should be stored for
     * @return boolean
     */
    public function set(string $key, $value, array $options = []): bool
    {
        $options += ['duration' => null];

        if ($options['duration']) {
            return $this->client->setex($key, $options['duration'], $value);
        }

        return  $this->client->set($key, $value);
    }

    /**
     * Gets a value from Redis
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $value = $default;
        if ($this->client->exists($key)) {
            $value = $this->client->get($key);
        }

        return $value;
    }

    /**
     * Check if a key exists in redis
     *
     * @param string $key
     * @return boolean
     */
    public function exists(string $key): bool
    {
        return (bool) $this->client->exists($key);
    }

    /**
     * Deletes a key
     *
     * @param string $key
     * @return boolean
     */
    public function delete(string $key): bool
    {
        return (bool) $this->client->del($key);
    }

    /**
     * Increases a value by
     *
     * @param string $key
     * @param integer $count
     * @return integer $count the new value
     */
    public function increment(string $key, int $count = 1)
    {
        return $this->client->incrby($key, $count);
    }

    /**
     * Decreases a value by
     *
     * @param string $key
     * @param integer $count
    * @return integer $count the new value
     */
    public function decrement(string $key, int $count = 1)
    {
        return $this->client->decrby($key, $count);
    }

    /**
     * Gets the list of keys
     *
     * @return array
     */
    public function keys(): array
    {
        return $this->client->keys('*');
    }

    /**
     * Removes all keys from the current database
     *
     * @return boolean
     */
    public function flush(): bool
    {
        return $this->client->flushDB();
    }

    /**
     * Gets PHP Redis extension client
     *
     * @return \Redis
     */
    public function client(): \Redis
    {
        return $this->client;
    }

    /**
     * @param array $config
     * @return \Redis
     */
    private static function buildClient(array $config): \Redis
    {
        if (! extension_loaded('redis')) {
            throw new RuntimeException('Redis extension not loaded.');
        }
  
        $redis = new \Redis();
        $result = false;
        try {
            if (! empty($config['path'])) {
                $result = $redis->connect($config['path']);
            } elseif (! empty($config['persistent'])) {
                $id = ($config['persistent'] === true) ? 'origin-php' : (string) $config['persistent'];
                $result = $redis->pconnect($config['host'], $config['port'], $config['timeout'], $id);
            } else {
                $result = $redis->connect($config['host'], $config['port'], $config['timeout']);
            }
            if ($result && isset($config['password'])) {
                $result = $redis->auth($config['password']);
            }
        } catch (\RedisException $e) {
            $result = false;
        }

        if (! $result) {
            throw new RuntimeException('Error connecting to Redis server.');
        }

        if (isset($config['database'])) {
            $result = $redis->select((int) $config['database']);
        }

        if (! empty($config['prefix'])) {
            $redis->setOption(\Redis::OPT_PREFIX, $config['prefix']);
        }

        return $redis;
    }
}
