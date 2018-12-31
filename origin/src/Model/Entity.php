<?php
/**
 * OriginPHP Framework
 * Copyright 2018 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Entity Object
 * Entity is an object that represents a single row in a database.
 * Moving away from arrays, we want this to work similar e.g isset, empty array_keys.
 */

namespace Origin\Model;

class Entity
{
    /**
     * Holds the properties and values for this entity.
     *
     * @var array
     */
    protected $_properties = [];

    /**
     * Holds the validation errors for this entity (not nested).
     *
     * @var array
     */
    protected $_validationErrors = [];

    /**
     * The name of this entity, alias of the model.
     *
     * @var string
     */
    protected $_name = null;

    public function __construct(array $properties = [], array $options = [])
    {
        $this->_properties = $properties;
        if (isset($options['name'])) {
            $this->_name = $options['name'];
        }
    }

    public function __set($property, $value)
    {
        $this->_properties[$property] = $value;

        return $this;
    }

    /**
     * Added & to prevent Indirect modification of overloaded property errors. change nulled
     * to $value for Only variable references should be returned by reference errors. and referenced
     * result.
     */
    public function &__get($property)
    {
        $value = null;
        if (isset($this->_properties[$property])) {
            $value = &$this->_properties[$property];
        }

        return $value;
    }

    public function __debugInfo()
    {
        return $this->_properties;
    }

    public function __isset($property)
    {
        return isset($this->_properties[$property]);
    }

    public function __unset($property)
    {
        if (isset($this->_properties[$property])) {
            unset($this->_properties[$property]);
        }

        return $this;
    }

    public function invalidate(string $field, string $message)
    {
        if (!isset($this->_validationErrors[$field])) {
            $this->_validationErrors[$field] = [];
        }
        $this->_validationErrors[$field][] = $message;
    }

    public function errors()
    {
        return $this->_validationErrors;
    }

    public function hasError(string $field)
    {
        return isset($this->_validationErrors[$field]);
    }

    public function getError(string $field)
    {
        if (isset($this->_validationErrors[$field])) {
            return $this->_validationErrors[$field];
        }

        return null;
    }

    public function unset($properties)
    {
        foreach ((array) $properties as $key) {
            unset($this->_properties[$key]);
        }

        return $this;
    }

    /**
     * Added & to prevent Indirect modification of overloaded property errors.
     */
    public function &get($properties)
    {
        if (is_string($properties)) {
            if (isset($this->_properties[$properties])) {
                return $this->_properties[$properties];
            }

            return null;
        }

        $result = [];
        foreach ($properties as $property) {
            if (isset($this->_properties[$property])) {
                $result[$property] = $this->_properties[$property];
            }
        }

        return $result;
    }

    /**
     * Sets a property/properties of the entity.
     *
     * @param string|array $property $properties
     * @param mixed        $value
     */
    public function set($properties, $value = null)
    {
        if (is_string($properties)) {
            $this->_properties[$properties] = $value;

            return $this;
        }

        foreach ($properties as $key => $value) {
            $this->_properties[$key] = $value;
        }

        return $this;
    }

    /**
     * Clears the current entity properties.
     */
    public function clear()
    {
        $this->_properties = [];
    }

    /**
     * Checks if Entity has property set. This SHOULD work like isset.
     *
     * @param string $property name of property
     *
     * @return bool true of false
     */
    public function hasProperty($property)
    {
        return isset($this->_properties[$property]);
    }

    /**
     * Returns a list of properties for the Entity.
     * Use this instead of get_object_vars.
     *
     * @return array properties
     */
    public function properties()
    {
        return array_keys($this->_properties);
    }

    /**
     * Checks if a entity has a property SET (regardless if null).
     *
     * @param string $property [description]
     *
     * @return bool [description]
     */
    public function propertyExists(string $property)
    {
        return array_key_exists($property, $this->_properties);
    }

    /**
     * Gets the model.
     *
     * @return string model name
     */
    public function name()
    {
        return $this->_name;
    }

    /**
     * Converts the Entity into an array.
     *
     * @return array result
     */
    public function toArray()
    {
        $result = [];
        foreach ($this->_properties as $property => $value) {
            if ($value instanceof Entity) {
                $result[$property] = $value->toArray();
            } elseif (is_array($value)) {
                foreach ($value as $k => $v) {
                    if ($v instanceof Entity) {
                        $result[$property][$k] = $v->toArray();
                    }
                }
            } else {
                $result[$property] = $value;
            }
        }

        return $result;
    }
}
