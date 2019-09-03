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

use Origin\Utility\Xml;
use Origin\Utility\Inflector;

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
     * If the record exists in the database (set during find)
     *
     * @var [type]
     */
    protected $_exists = null;

    /**
     * Holds modified fields
     *
     * @var array
     */
    protected $_modified = [];

    /**
     * The entity is new and inserted into database
     *
     * @var boolean
     */
    protected $_created = false;
  
    /**
     * If the entity was deleted
     *
     * @var boolean
     */
    protected $_deleted = false;
    
    /**
     * Constructor
     *
     * @param array $properties data
     * @param array $options
     * - name: Model name
     * - exists: if the model exists in the database (set during find), null, means dont know
     * - markClean: mark the entity as clean after creation. This is useful for when loading records
     * from the database.
     */
    public function __construct(array $properties = [], array $options = [])
    {
        $options += ['name' => null, 'exists' => null, 'markClean' => false];

        $this->_name = $options['name'];
        $this->_exists = $options['exists'];

        foreach ($properties as $property => $value) {
            $this->set($property, $value);
        }
        if ($options['markClean']) {
            $this->reset();
        }
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
     * Magic method is trigged when calling var_dump
     *
     * @return array
     */
    public function __debugInfo()
    {
        return $this->_properties;
    }

    /**
     * Magic method is trigged when the object is treated as string,
     * e.g. echo $entity
     * @return string
     */
    public function __toString()
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }

    /**
     * Handles the entity errors, can set, get and check
     *
     *  $errors = $entity->errors();
     *  $fieldErrors = $entity->errors('contact_name');
     *  $entity->errors('email','invalid email address');
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
        $this->invalidate($field, $error);
    }

    /**
     * Sets a validation error
     *
     * @param string $field
     * @param string $error
     * @return void
     */
    public function invalidate(string $field, string $error)
    {
        if (! isset($this->_errors[$field])) {
            $this->_errors[$field] = [];
        }
        $this->_errors[$field][] = $error;
    }

    /**
     * Unsets a property or array of properties
     *
     * @param string|array $properties
     * @return \Origin\Model\Entity;
     */
    public function unset($properties)
    {
        foreach ((array)$properties as $key) {
            unset($this->_properties[$key]);
            unset($this->_modified[$key]);
        }

        return $this;
    }

    /**
     * Added & to prevent Indirect modification of overloaded property errors.
     * @return mixed
     */
    public function &get(string $property)
    {
        $result = null;
        if (isset($this->_properties[$property])) {
            $result = &$this->_properties[$property];
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
     * Resets the modified properties
     *
     * @return void
     */
    public function reset()
    {
        $this->_modified = [];
        $this->_errors = [];
    }

    /**
     * If the record is a newly created record
     *
     * @param boolean $created
     * @return boolean
     */
    public function created(bool $created = null) : bool
    {
        return $this->setGetPersisted('created', $created);
    }
    /**
     * If the record was deleted
     *
     * @param boolean $deleted
     * @return boolean
     */
    public function deleted(bool $deleted = null) : bool
    {
        return $this->setGetPersisted('deleted', $deleted);
    }

    private function setGetPersisted(string $type, bool $value = null)
    {
        $var = '_' . $type;

        if ($value === null) {
            return $this->$var;
        }

        return $this->$var = $value;
    }

    /**
     * Returns the list of properties that have been modified, if you supply
     * a property name, then it will return a boolean result to tell you if the property has
     * been changed.
     *
     * $array = $entity->modified();
     * $bool = $entity->modified('email');
     *
     * @return array|bool
     */
    public function modified(string $property = null)
    {
        if ($property === null) {
            return array_keys($this->_modified);
        }

        return isset($this->_modified[$property]);
    }

    /**
     * Checks if Entity has property set. This SHOULD work like isset.
     *
     * @param string $property name of property
     * @return bool true of false
     */
    public function has($property)
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
     * @param string $property
     * @return bool
     */
    public function propertyExists(string $property)
    {
        return array_key_exists($property, $this->_properties);
    }

    /**
     * Gets the entity name, aka the model or alias.
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
            if (is_array($value) or $value instanceof Collection) {
                foreach ($value as $k => $v) {
                    if ($v instanceof Entity) {
                        $result[$property][$k] = $v->toArray();
                    }
                }
                continue;
            }
            if ($value instanceof Entity) {
                $value = $value->toArray();
            }
            $result[$property] = $value;
        }

        return $result;
    }

    /**
     * Converts this entity into Json
     *
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }

    /**
     * Converts this entity into XML
     *
     * @return string
     */
    public function toXml()
    {
        $root = Inflector::camelCase($this->_name ?? 'record');

        return Xml::fromArray([$root => $this->toArray()]);
    }
}
