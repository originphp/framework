<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright     Copyright (c) Jamiel Sharief
 * @link         https://www.originphp.com
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
declare(strict_types = 1);
namespace Origin\Redis;

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

use Redis;
use RedisException;
use Origin\Core\Exception\Exception;

class RedisConnection
{
    /**
     * Connects to Redis
     *
     * @param array $config
     * @return Redis
     */
    public static function connect(array $config) : Redis
    {
        $config += [
            'host' => '127.0.0.1','port' => 6379,'password' => null,'timeout' => 0,'persistent' => true, // Faster!!!
            'path' => null];

        if (! extension_loaded('redis')) {
            throw new Exception('Redis extension not loaded.');
        }
        $redis = new Redis();
        $result = false;
        try {
            if (! empty($config['path'])) {
                $result = $redis->connect($config['path']);
            } elseif (! empty($config['persistent'])) {
                $id = ($config['persistent'] === true)?'origin-php':(string)$config['persistent'];
                $result = $redis->pconnect($config['host'], $config['port'], $config['timeout'], $id);
            } else {
                $result = $redis->connect($config['host'], $config['port'], $config['timeout']);
            }
        } catch (RedisException $e) {
            // result still false
        }

        if ($result && isset($config['password'])) {
            $result = $redis->auth($config['password']);
        }

        if (! $result) {
            throw new Exception('Error connecting to Redis server.');
        }
       
        return $redis;
    }
}
