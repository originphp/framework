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

use Origin\Core\ConfigTrait;

class CacheEngine
{
    use ConfigTrait;

    /**
     * Constructor
     *
     * @param array $config  duration,prefix,path
     */
    public function __construct(array $config=[])
    {
        $this->setConfig($config);
        $this->initialize($config);
    }

    public function initialize(array $config)
    {
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
        return true;
    }
    /**
     * Gets the value;
     * @todo returns false always
     * @param string $key
     * @return void
     */
    public function get(string $key)
    {
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
        return false;
    }
    /**
     * Deletes a key from the cache
     *
     * @param string $key
     * @return boolean
     */
    public function delete(string $key) :bool
    {
        return true;
    }

    /**
     * Clears the Cache
     *
     * @return boolean
     */
    public function clear(): bool
    {
        return false;
    }

    /**
     * Increases a value
     *
     * @param string $key
     * @param integer $offset
     * @return integer
     */
    public function increment(string $key, int $offset = 1)
    {
        return true;
    }

    /**
     * Decreases a value
     *
     * @param string $key
     * @param integer $offset
     * @return integer
     */
    public function decrement(string $key, int $offset = 1)
    {
        return true;
    }

    /**
     * Creates a friendly key for use with caching engines
     *
     * @param string $key
     * @return string
     */
    protected function key(string $key) : string
    {
        return $this->config['prefix'] . preg_replace('/[^a-z0-9-]+/i', '_', $key);
    }

    /**
     * Returns a string id for persistent connections
     *
     * @return string
     */
    protected function persistentId() : string
    {
        if ($this->config['persistent'] === true) {
            return 'origin-php';
        }
        return (string) $this->config['persistent'];
    }
}
