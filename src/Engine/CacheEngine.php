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

namespace Origin\Engine;

use Origin\Core\ConfigTrait;

abstract class CacheEngine
{
    use ConfigTrait;

    /**
     * Constructor
     *
     * @param array $config  duration,prefix,path
     */
    public function __construct(array $config=[])
    {
        $this->config($config);
        $this->initialize($config);
    }

    public function initialize(array $config)
    {
    }

    /**
     * writes a value in the cache
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    abstract public function write(string $key, $value);
    
    /**
     * reads the value;
     * @todo returns false always
     * @param string $key
     * @return void
     */
    abstract public function read(string $key);

    /**
     * Checks if a key exists in the cache
     *
     * @param string $key
     * @return boolean
     */
    abstract public function exists(string $key);
    /**
     * Deletes a key from the cache
     *
     * @param string $key
     * @return boolean
     */
    abstract public function delete(string $key);

    /**
     * Clears the Cache
     *
     * @return boolean
     */
    abstract public function clear();

    /**
     * Increases a value
     *
     * @param string $key
     * @param integer $offset
     * @return integer
     */
    abstract public function increment(string $key, int $offset = 1);

    /**
     * Decreases a value
     *
     * @param string $key
     * @param integer $offset
     * @return integer
     */
    abstract public function decrement(string $key, int $offset = 1);

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
