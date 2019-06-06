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
 * echo 'apc.enable_cli=1' >>  /etc/php/7.2/cli/php.ini
 */
namespace Origin\Engine\Cache;

use Origin\Engine\CacheEngine;

use Origin\Exception\Exception;

class ApcuEngine extends CacheEngine
{
    protected $defaultConfig = [
        'duration' => 3600,
        'prefix' => 'origin_'
    ];

    public function initialize(array $config)
    {
        if (!extension_loaded('apcu')) {
            throw new Exception('Apcu extension not loaded.');
        }
  
        $enabled = ini_get('apc.enabled');
        if (PHP_SAPI == 'cli') {
            $enabled = (bool) ini_get('apc.enable_cli');
        }
        if (!$enabled) {
            throw new Exception('Apcu extension not enabled check apc.enabled and apc.enable_cli.');
        }
    }
    /**
     * writes a value in the cache
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function write(string $key, $value) :bool
    {
        return apcu_store($this->key($key), $value, $this->config['duration']);
    }
    /**
     * reads a value from the cache
     * @todo returns false always
     * @param string $key
     * @return void
     */
    public function read(string $key)
    {
        return apcu_fetch($this->key($key));
    }
    /**
     * Checks if a key exists in the cache
     *
     * @param string $key
     * @return boolean
     */
    public function exists(string $key) :bool
    {
        return apcu_exists($this->key($key));
    }
    /**
     * Deletes a kehy from the cache
     *
     * @param string $key
     * @return boolean
     */
    public function delete(string $key) :bool
    {
        return apcu_delete($this->key($key));
    }

    /**
     * Clears the Cache
     *
     * @return boolean
     */
    public function clear(): bool
    {
        return apcu_clear_cache();
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
    public function increment(string $key, int $offset = 1): int
    {
        return apcu_inc($this->key($key), $offset);
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
        return apcu_dec($this->key($key), $offset);
    }
}
