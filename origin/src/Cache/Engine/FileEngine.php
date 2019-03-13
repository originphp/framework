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
 * File cache should only be used for storing large objects or sets of data
 */

namespace Origin\Cache\Engine;

use Origin\Cache\CacheEngine;
use Origin\Exception\Exception;

class FileEngine extends CacheEngine
{
    protected $defaultConfig = [
        'path' => TMP . '/cache',
        'duration' => 3600,
        'prefix' => 'cache_'
    ];

    /**
     * Sets a value in the cache
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function set(string $key, $value) :bool
    {
        return file_put_contents($this->config['path'] . DS . $this->key($key), serialize($value));
    }
    /**
     * Gets the value;
     *
     * @param string $key
     * @return void
     */
    public function get(string $key)
    {
        if ($this->has($key)) {
            $filename = $this->config['path'] . DS . $this->key($key);
            $expires = filemtime($filename) + $this->config['duration'];
            if ($expires > time()) {
                return unserialize(file_get_contents($filename));
            }
        }
        return false;
    }
    /**
     * Checks if a key exists in the cache
     *
     * @param string $key
     * @return boolean
     */
    public function has(string $key) :bool
    {
        return file_exists($this->config['path'] . DS . $this->key($key));
    }
    /**
     * Deletes a kehy from the cache
     *
     * @param string $key
     * @return boolean
     */
    public function delete(string $key) :bool
    {
        if ($this->has($key)) {
            return unlink($this->config['path'] . DS . $this->key($key));
        }
        return false;
    }

    /**
     * Clears the file cache
     *
     * @return boolean
     */
    public function clear() : bool
    {
        $files = scandir($this->config['path']);
        $result = [];
        foreach ($files as $file) {
            if (substr($file, 0, strlen($this->config['prefix'])) == $this->config['prefix']) {
                $result[] = (unlink($this->config['path'] . DS . $file) === true);
            }
        }
        return !in_array(false, $result);
    }

    public function increment(string $key, int $offset = 1)
    {
        throw new Exception('File cache cannot be incremented.');
    }

    public function decrement(string $key, int $offset = 1)
    {
        throw new Exception('File cache cannot be decremented.');
    }
}
