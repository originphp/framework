<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
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
    protected $_errors = [];

    /**
     * The name of this entity, alias of the model.
     *
     * @var string
     */
    protected $_name = null;

    /**
     * Holds modified fields
     *
     * @var array
     */
    protected $_modified = [];

    /**
     * Wether this is a new or existingh record. Null means doesnt know
     *
     * @var bool
     */
    protected $_new = null;

    /**
     * Undocumented function
     *
     * List of options
     *  - name: Model name
     *  - new: wether this is  a new record or not
     *
     * @param array $properties data
     * @param array $options
     */
    public function __construct(array $properties = [], array $options = [])
    {
        $options += ['name'=>null,'new'=>null];

        $this->_name = $options['name'];
        $this->_new = $options['new'];
        $this->_properties = $properties;
    }

    public function __set($property, $value)
    {
        $this->_properties[$property] = $value;
        $this->_modified[$property] = true;
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

    public function __toString()
    {
        return json_encode($this->_properties, JSON_PRETTY_PRINT);
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

    /**
     * Handles the entity errors, can set, get and check
     *
     *  $errors = $entity->errors();
     *  $fieldErrors = $entity->errors('contact_name');
     *  $entity->errors('email','invalid email address');
     *  $entity->errors('password',['alphanumeric only','min length must be 5']);
     *
     * @param string $field
     * @param string|array $error
     * @return null|array
     */
    public function errors(string $field = null, string $error = null)
    {
        if ($field === null) {
            return $this->_errors;
        }
        if ($error === null) {
            if (isset($this->_errors[$field])) {
                return $this->_errors[$field];
            }
    
            return null;
        }

        if (!isset($this->_errors[$field])) {
            $this->_errors[$field] = [];
        }

        $error = (array) $error;
        foreach ($error as $message) {
            $this->_errors[$field][] = $message;
        }
    }

    public function unset($properties)
    {
        foreach ((array) $properties as $key) {
            unset($this->_properties[$key]);
            unset($this->_modified[$key]);
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
        if (is_array($properties) === false) {
            $properties = [$properties => $value];
        }

        foreach ($properties as $key => $value) {
            $this->_properties[$key] = $value;
            $this->_modified[$key] = true;
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
     * Resets the modified properties
     *
     * @return void
     */
    public function clean()
    {
        $this->_modified = [];
    }

    /**
     * Returns the fields that modified
     *
     * @return array
     */
    public function modified()
    {
        return array_keys($this->_modified);
    }

    /**
     * Sets or gets the new propery
     *
     * @param boolean $new
     * @return boolean
     */
    public function isNew(bool $new = null)
    {
        if ($new === null) {
            return $this->_new;
        }
        return $this->_new = $new;
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
