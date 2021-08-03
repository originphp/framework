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
namespace Origin\Model;

use Origin\Inflector\Inflector;

/**
 * BaseEntity class, used by Record and Entity will be refactored to use this.
 */
class BaseEntity
{
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
     * Name of this entity, e.g User, Book
     *
     * @var string
     */
    protected $entityName = null;

    /**
     * Holds data
     *
     * @var array
     */
    private $entityData = [];

    /**
     * Holds the dirty fields
     *
     * @var array
     */
    private $entityDirty = [];

    /**
     * Holds the data that has been changed
     *
     * @var array
     */
    private $entityChanged = [];

    /**
     * Holds the errors
     *
     * @var array
     */
    private $entityErrors = [];

    /**
    * Cached lists of accessors
    *
    * @var array
    */
    protected static $accessors = [];

    /**
     * Sets or gets the name for this object
     *
     * @param string $name
     * @return string|null
     */
    public function name(string $name = null): ? string
    {
        if ($name === null) {
            return $this->entityName;
        }

        return $this->entityName = $name;
    }

    /**
    * Checks if property set and has a non null value
    *
    * @param string $key
    * @return boolean
    */
    public function has(string $key): bool
    {
        return isset($this->entityData[$key]);
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
        
        if (isset($this->entityData[$key])) {
            $value = &$this->entityData[$key];
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

            if (! array_key_exists($key, $this->entityChanged) &&
                array_key_exists($key, $this->entityData) &&
                $value !== $this->entityData[$key]
                ) {
                $this->entityChanged[$key] = $this->entityData[$key] ;
            }

            $this->entityData[$key] = $value;
            $this->entityDirty[$key] = true;
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
        if (isset($this->entityData[$key])) {
            unset($this->entityData[$key] , $this->entityDirty[$key] , $this->entityChanged[$key]);

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
            return $this->entityErrors;
        }

        return $this->entityErrors[$field] ?? null;
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
        if (! isset($this->entityErrors[$field])) {
            $this->entityErrors[$field] = [];
        }
        $this->entityErrors[$field][] = $message;
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

            if (is_object($value) && method_exists($value, 'toArray')) {
                $value = $value->toArray();
            } elseif (is_iterable($value)) {
                foreach ($value as $k => $v) {
                    if (is_object($v) && method_exists($v, 'toArray')) {
                        $out[$property][$k] = $v->toArray();
                    } elseif (is_scalar($v)) {
                        $out[$property][$k] = $v;
                    }
                }
                continue;
            }
            $out[$property] = $value;
        }

        return $out;
    }

    /**
     * Checks if data or an attribute has been modified (dirty)
     *
     * @param string $property
     * @return boolean
     */
    public function isDirty(string $property = null): bool
    {
        if ($property === null) {
            return ! empty($this->entityDirty);
        }

        return isset($this->entityDirty[$property]);
    }

    /**
     * Checks if data or an attribute has not been modified
     *
     * @param string $property
     * @return boolean
     */
    public function isClean(string $property = null): bool
    {
        return ! $this->isDirty($property);
    }

    /**
     * Checks if a value was changed from the original
     *
     * @param string $property
     * @return boolean
     */
    public function wasChanged(string $property = null): bool
    {
        if ($property === null) {
            return ! empty($this->entityChanged);
        }

        return isset($this->entityChanged[$property]);
    }

    /**
     * Gets the changed fields or value for a particular attribute
     *
     * @param string $property
     * @return mixed
     */
    public function changed(string $property = null)
    {
        if ($property === null) {
            return $this->entityChanged;
        }

        return $this->entityChanged[$property] ?? null;
    }

    /**
    * Returns the list of properties that have been modified, if you supply
    * a property name, then it will return a boolean result to tell you if the property has
    * been changed. (wrapper for dirty)
    *
    * $array = $entity->modified();
    * $bool = $entity->modified('email');
    *
    * @internal this goes with changed, and uses dirty (same thing)
    *
    * @return array|bool
    */
    public function modified(string $property = null)
    {
        return $this->dirty($property);
    }

    /**
     * Gets the dirty fields or returns a boolean result if the property was made dirty
     *
     * @param string $property
     * @return mixed
     */
    public function dirty(string $property = null)
    {
        if ($property === null) {
            return array_keys($this->entityDirty);
        }

        return isset($this->entityDirty[$property]);
    }

    /**
     * Cleans the state of this container
     *
     * @return void
     */
    public function reset(): void
    {
        $this->entityChanged = [];
        $this->entityDirty = [];
        $this->entityErrors = [];
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
      * Gets the list of properties on this entity
      *
      * @return array properties
      */
    public function properties(): array
    {
        return array_keys($this->entityData);
    }

    /**
     * Checks that the value is not null, is not an empty string or an empty array. This works similar
     * to validation rule, but not PHP empty which will check false as well.
     *
     * @param string $property
     * @return boolean
     */
    public function isEmpty(string $property): bool
    {
        $value = $this->get($property);

        return $value === null || $value === '' || $value === [];
    }

    /**
     * Checks if a property has a non-empty value (see isEmpty)
     *
     * @param string $property
     * @return boolean
     */
    public function notEmpty(string $property): bool
    {
        return ! $this->isEmpty($property);
    }

    /**
     * Checks if this entity has any errors
     *
     * @return boolean
     */
    public function hasErrors(): bool
    {
        return ! empty($this->entityErrors);
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        $data = $this->entityData;
        foreach ($this->virtual as $field) {
            $data[$field] = $this->$field;
        }
        $extra = [
            '*name' => $this->entityName,
            '*dirty' => $this->isDirty() ? 'true' : 'false',
            '*changed' => ! empty($this->changed()) ? 'true' : 'false',
            '*errors' => $this->entityErrors
        ];

        return $extra + $data;
    }

    /**
     * Gets the visible properties for this object
     *
     *
     * @return array
     */
    private function visibleProperties(): array
    {
        $properties = array_merge(array_keys($this->entityData), $this->virtual);

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
