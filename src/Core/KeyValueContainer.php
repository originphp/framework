<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2021 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */
declare(strict_types = 1);
namespace Origin\Core;

use ArrayAccess;

/**
 * This is a brand new class, please don't use directly as it is subject to change
 */
class KeyValueContainer implements ArrayAccess
{
    /**
     * Container data
     *
     * @var array
     */
    protected $data = [];

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Sets a value for this container
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function set(string $key, $value)
    {
        return $this->data[$key] = $value;
    }

    /**
     * Gets a value from this container
     *
     * @param string $key
     * @return void
     */
    public function get(string $key)
    {
        return $this->data[$key] ?? null;
    }

    /**
     * Checks if a value exists in this container
     *
     * @param string $key
     * @return boolean
     */
    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * Delets a value from this container
     *
     * @param string $key
     * @return boolean
     */
    public function delete(string $key): bool
    {
        if ($this->has($key)) {
            unset($this->data[$key]);

            return true;
        }

        return false;
    }

    /**
     * Gets all key and values for this container
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Lists all the keys for this container
     *
     * @return array
     */
    public function list(): array
    {
        return array_keys($this->data);
    }

    /**
     * Checks if this container is empty
     *
     * @return boolean
     */
    public function isEmpty(): bool
    {
        return empty($this->data);
    }

    /**
     * ArrayAccess method
     *
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    /**
     * ArrayAccess method
     *
     * @param  mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * ArrayAccess method
     *
     * @param mixed] $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * ArrayAccess method
     *
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }
}
