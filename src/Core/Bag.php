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

use Countable;
use ArrayAccess;
use Serializable;
use ArrayIterator;
use JsonSerializable;
use IteratorAggregate;

class Bag implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable, Serializable
{
    /**
     * Container data
     *
     * @var array
     */
    protected $data = [];

    /**
     * Constructor
     *
     * @param array $data data to set
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Sets a value for this bag
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
     * Gets a value from this bag
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function &get(string $key, $default = null)
    {
        $value = $default;

        if (array_key_exists($key, $this->data)) {
            $value = &$this->data[$key];
        }

        return $value;
    }

    /**
     * Checks if a value exists in this bag
     *
     * @param string $key
     * @return boolean
     */
    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * Delets a value from this bag
     *
     * @param string $key
     * @return boolean
     */
    public function remove(string $key): bool
    {
        if (isset($this->data[$key])) {
            unset($this->data[$key]);

            return true;
        }

        return false;
    }

    /**
     * Lists all the keys for all items in this bag
     *
     * @return array
     */
    public function list(): array
    {
        return array_keys($this->data);
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

    /**
     * Clears this object
     *
     * @return void
     */
    public function clear(): void
    {
        $this->data = [];
    }

    /**
    * Gets the count of items in the bag, also part of the Countable interface
    *
    * @return integer
    */
    public function count(): int
    {
        return count($this->data);
    }

    /**
     * IteratorAggregate interface
     *
     * @return void
     */
    public function getIterator()
    {
        return new ArrayIterator($this->data);
    }

    /**
     * Specify data which should be serialized to JSON (JsonSerializable interface)
     *
     * @return void
     */
    public function jsonSerialize()
    {
        return $this->data;
    }

    /**
     * Magic method for setting data on inaccessible properties.
     *
     * @param string $property
     * @param mixed $value
     * @return void
     */
    public function __set(string $property, $value)
    {
        $this->set($property, $value);
    }

    /**
     * Magic method to get data from inaccessible properties.
     *
     * @param string $property
     * @return mixed
     */
    public function &__get(string $property)
    {
        return $this->get($property);
    }

    /**
     * Magic method is triggered by calling isset() or empty() on inaccessible properties.
     *
     * @param string $property
     * @return boolean
     */
    public function __isset(string $property)
    {
        return $this->has($property);
    }

    /**
     * Magic method is triggered by unset on inaccessible properties.
     *
     * @param string $property
     * @return boolean
     */
    public function __unset(string $property)
    {
        $this->remove($property);
    }

    /**
     * Returns a string representation of this object
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * Converts this bag to json
     *
     * @param array $options The following options are supported
     *  - pretty: default:false Use pretty print
     * @return string
     */
    public function toJson(array $options = []): string
    {
        $options += ['pretty' => false];

        return json_encode($this->data, $options['pretty'] ? JSON_PRETTY_PRINT : 0);
    }

    /**
     * Gets all key and values for this bag
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Serializes this object (Serializable interface)
     *
     * @return void
     */
    public function serialize()
    {
        return serialize($this->data);
    }

    /**
     * Unserializes this object (Serializable interface)
     *
     * @param string $data
     * @return void
     */
    public function unserialize($data)
    {
        $this->data = unserialize($data);
    }
}
