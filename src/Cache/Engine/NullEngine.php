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
 * NullCache is for disabling cache
 */
namespace Origin\Cache\Engine;

class NullEngine extends BaseEngine
{
    /**
     * Sets a value in the cache
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function write(string $key, $value) :bool
    {
        return true;
    }
    /**
     * Gets the value;
     * @todo returns false always
     * @param string $key
     * @return mixed // keep consistent
     */
    public function read(string $key)
    {
        return false;
    }
    /**
     * Checks if a key exists in the cache
     *
     * @param string $key
     * @return boolean
     */
    public function exists(string $key) :bool
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
}
