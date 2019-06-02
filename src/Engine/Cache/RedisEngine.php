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
/**
 * Should work with phpredis
 * @see https://github.com/phpredis/phpredis
 *
 * Add to docker
 *  redis:
 *  image: redis
 *
 * pecl install redis
 * echo 'extension=redis.so' >> /etc/php/7.2/cli/php.ini
 */

namespace Origin\Engine\Cache;

use Origin\Core\ConfigTrait;
use Origin\Exception\Exception;
use Origin\Engine\CacheEngine;
use Redis;

class RedisEngine extends CacheEngine
{
    use ConfigTrait;

    /**
     * Redis Object
     *
     * @var Redis
     */
    protected $Redis = null;

    protected $defaultConfig = [
        'host' => '127.0.0.1',
        'port' =>  6379,
        'password' => null,
        'timeout' => 0,
        'persistent' => true, // Faster!!!
        'path' => null, // Path to redis unix socket,
        'duration' => 3600,
        'prefix' => 'origin_'
    ];

    /**
     * Constructor
     *
     * @param array $config  duration,prefix,path
     */
    public function initialize(array $config)
    {
        if (!extension_loaded('redis')) {
            throw new Exception('Redis extension not loaded.');
        }
        $this->Redis = new Redis();
        if (!$this->connect()) {
            throw new Exception('Error connecting to Redis server.');
        }
    }

    protected function connect()
    {
        $result = false;
        if ($this->config['path']) {
            $result = $this->Redis->connect($this->config['path']);
        } elseif ($this->config['persistent']) {
            $result = $this->Redis->pconnect($this->config['host'], $this->config['port'], $this->config['timeout'], $this->persistentId());
        } else {
            $result = $this->Redis->connect($this->config['host'], $this->config['port'], $this->config['timeout']);
        }
        if ($result and isset($this->config['password'])) {
            $result = $this->Redis->auth($this->config['password']);
        }
        return $result;
    }
    /**
     * Sets a value in the cache
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function set(string $key, $value) :bool
    {
        if ($this->config['duration'] === 0) {
            return $this->Redis->set($this->key($key), $value);
        }
        return $this->Redis->setex($this->key($key), $this->config['duration'], $value);
    }
    /**
     * Gets the value;
     * @todo returns false always
     * @param string $key
     * @return void
     */
    public function get(string $key)
    {
        return $this->Redis->get($this->key($key));
    }
    /**
     * Checks if a key exists in the cache
     *
     * @param string $key
     * @return boolean
     */
    public function has(string $key) :bool
    {
        return $this->Redis->exists($this->key($key));
    }
    /**
     * Deletes a key from the cache
     *
     * @param string $key
     * @return boolean
     */
    public function delete(string $key) :bool
    {
        return $this->Redis->delete($this->key($key));
    }

    /**
     * Clears the Cache
     *
     * @return bool
     */
    public function clear() :bool
    {
        $keys =  $this->Redis->getKeys($this->config['prefix'] . '*');
        $result = [];
        foreach ($keys as $key) {
            $result[] = (bool) $this->Redis->delete($key);
        }
        return !in_array(false, $result);
    }

    public function __destruct()
    {
        if (!$this->config['persistent'] and $this->Redis instanceof Redis) {
            $this->Redis->close();
        }
    }

    /**
     * Increases a value
     *
     *  Cache::write('my_value',100);
     *  $value = Cache::increment('my_value');
     *
     * @param string $key
     * @param integer $offset
     * @return integer
     */
    public function increment(string $key, int $offset = 1) : int
    {
        $key = $this->key($key);
        $value = (int) $this->Redis->incrBy($key, $offset);
        if ($this->config['duration']>0) {
            $this->Redis->setTimeout($key, $this->config['duration']);
        }
        return $value;
    }


    /**
     * Decreases a value
     *
     * @param string $key
     * @param integer $offset
     * @return integer
     */
    public function decrement(string $key, int $offset = 1): int
    {
        $key = $this->key($key);
        $value = (int) $this->Redis->decr($key, $offset);
        if ($this->config['duration']>0) {
            $this->Redis->setTimeout($key, $this->config['duration']);
        }
        return $value;
    }
}
