<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
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

/**
 * Container Trait.
 *
 * This is used by Record & will be used by Entity (@todo refactor)
 */
trait ContainerTrait
{
    /**
     * Holds container data in a protected array, important for flexability.
     *
     * @var array
     */
    protected $containerData = [];

    /**
    * Checks if property set and has a non null value
    *
    * @param string $key
    * @return boolean
    */
    public function has(string $key): bool
    {
        return isset($this->containerData[$key]);
    }

    /**
     * Gets a value
     *
     * @param string $key
     * @return mixed
     */
    public function &get(string $key)
    {
        $value = null;
        
        if (isset($this->containerData[$key])) {
            $value = &$this->containerData[$key];
        }

        return $value;
    }

    /**
     * Sets a value
     *
     * @param string|array $key
     * @param mixed $value
     * @return void
     */
    public function set($key, $value = null): void
    {
        $data = is_array($key) ? $key : [$key => $value];

        foreach ($data as $key => $value) {
            $this->containerData[$key] = $value;
        }
    }

    /**
    * Deletes a value
    *
    * @param string $key
    * @return boolean
    */
    public function unset(string $key): bool
    {
        if (isset($this->containerData[$key])) {
            unset($this->containerData[$key]);

            return true;
        }

        return false;
    }
    
    /**
     * Returns an array of data in the container
     *
     * @return void
     */
    public function toArray()
    {
        return $this->containerData;
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
        $this->unset($property);
    }

    /**
     * Magic method
     *
     * @return string
     */
    public function __toString()
    {
        return (string) json_encode($this->containerData, JSON_PRETTY_PRINT);
    }
}
