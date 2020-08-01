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
namespace Origin\Model;

use Origin\Inflector\Inflector;

/**
 * Container Trait.
 *
 * This is used by Record & will be used by Entity (@todo refactor)
 */
trait ContainerTrait
{
    /**
     * Holds data
     *
     * @var array
     */
    private $containerData = [];

    /**
     * Holds the dirty fields
     *
     * @var array
     */
    private $dirtyData = [];

    /**
     * Holds the data that has been changed
     *
     * @var array
     */
    private $changedData = [];

    /**
     * Holds the errors
     *
     * @var array
     */
    private $errors = [];

    /**
    * Cached lists of accessors
    *
    * @var array
    */
    protected static $accessors = [];

    /**
     * @var string
     */
    private $dataContainerName = null;

    /**
     * Virtual fields that will be exposed when using toArray, toJson and toXml
     *
     * @var array
     */
    protected $virtual = [];

    /**
     * Fields that should not be exposed when using toArray, toJson and toXml
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * Sets or gets the name for this object
     *
     * @param string $name
     * @return string|null
     */
    public function name(string $name = null): ? string
    {
        if ($name === null) {
            return $this->dataContainerName;
        }

        return $this->dataContainerName = $name;
    }

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
     * @param mixed $default default value
     * @return mixed
     */
    public function &get(string $key, $default = null)
    {
        $value = $default;
        
        $method = static::accessor($key, 'get');
        
        if (isset($this->containerData[$key])) {
            $value = &$this->containerData[$key];
        }

        if ($method) {
            $value = $this->$method($value);
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
            $method = static::accessor($key, 'set');
            if ($method) {
                $value = $this->$method($value);
            }

            if (! array_key_exists($key, $this->changedData) &&
                array_key_exists($key, $this->containerData) &&
                $value !== $this->containerData[$key]
                ) {
                $this->changedData[$key] = $this->containerData[$key] ;
            }

            $this->containerData[$key] = $value;
            $this->dirtyData[$key] = true;
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
            unset($this->containerData[$key] , $this->dirtyData[$key] , $this->changedData[$key]);

            return true;
        }

        return false;
    }
    
    /**
     * Gets all errors or for a specific field
     *
     * @param string $field
     * @return array|null
     */
    public function errors(string $field = null): ? array
    {
        if ($field === null) {
            return $this->errors;
        }

        return $this->errors[$field] ?? null;
    }

    /**
     * Sets an error
     *
     * @param string $field
     * @param string $message
     * @return void
     */
    public function error(string $field, string $message): void
    {
        if (! isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    /**
     * Returns an array of data in the container
     *
     * @return array
     */
    public function toArray(): array
    {
        $out = [];
        foreach ($this->visibleProperties() as $property) {
            $value = $this->$property;
            if (is_iterable($value)) {
                foreach ($value as $k => $v) {
                    if (is_object($v) && method_exists($v, 'toArray')) {
                        $out[$property][$k] = $v->toArray();
                    }
                }
                continue;
            } elseif (is_object($value) && method_exists($value, 'toArray')) {
                $value = $value->toArray();
            }
            $out[$property] = $value;
        }

        return $out;
    }

    /**
     * Checks if data or an attribute has been modified (dirty)
     *
     * @param string $attribute
     * @return boolean
     */
    public function isDirty(string $attribute = null): bool
    {
        if ($attribute === null) {
            return ! empty($this->dirtyData);
        }

        return isset($this->dirtyData[$attribute]);
    }

    /**
     * Checks if data or an attribute has not been modified
     *
     * @param string $attribute
     * @return boolean
     */
    public function isClean(string $attribute = null): bool
    {
        return ! $this->isDirty($attribute);
    }

    /**
     * Checks if a value was changed from the original
     *
     * @param string $attribute
     * @return boolean
     */
    public function wasChanged(string $attribute = null): bool
    {
        if ($attribute === null) {
            return ! empty($this->changedData);
        }

        return isset($this->changedData[$attribute]);
    }

    /**
     * Gets the changed fields or value for a particular attribute
     *
     * @param string $attribute
     * @return mixed
     */
    public function changed(string $attribute = null)
    {
        if ($attribute === null) {
            return $this->changedData;
        }

        return $this->changedData[$attribute] ?? null;
    }

    /**
     * Gets the dirty fields
     *
     * @param string $attribute
     * @return array
     */
    public function dirty(): array
    {
        return array_keys($this->dirtyData);
    }

    /**
     * Cleans the state of this container
     *
     * @return void
     */
    public function reset(): void
    {
        $this->changedData = [];
        $this->dirtyData = [];
        $this->errors = [];
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
     * Returns a string representation of this object
     *
     * @return string
     */
    public function __toString()
    {
        return (string) json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }

    /**
     * Gets the visible properties for this object
     *
     *
     * @return array
     */
    private function visibleProperties(): array
    {
        $properties = array_merge(array_keys($this->containerData), $this->virtual);

        return array_diff($properties, $this->hidden);
    }

    /**
     * Gets the accessor method
     *
     * @param string $property
     * @param string $type
     * @return mixed
     */
    protected static function accessor(string $property, string $type)
    {
        $class = static::class;

        if (isset(static::$accessors[$class][$type][$property])) {
            return static::$accessors[$class][$type][$property];
        }

        $method = $type . Inflector::studlyCaps($property);
        if (in_array($method, get_class_methods($class))) {
            return static::$accessors[$class][$type][$property] = $method;
        }
    }
}
