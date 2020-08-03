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

/**
 * Entity Object
 * Entity is an object that represents a single row in a database.
 * Moving away from arrays, we want this to work similar e.g isset, empty array_keys.
 * @internal whilst using _ might be considered bad/old practice in this case we want to prevent clashes with column names
 * in the database
 */

use ArrayAccess;
use Origin\Xml\Xml;
use JsonSerializable;
use Origin\Inflector\Inflector;

class Entity extends BaseEntity implements ArrayAccess, JsonSerializable
{
    /**
     * If entity exists in the database
     *
     * @var bool
     */
    private $entityExists = null;

    /**
     * The entity is new and inserted into database
     *
     * @var boolean
     */
    private $entityCreated = false;
  
    /**
     * If the entity was deleted from the database
     *
     * @var boolean
     */
    private $entityDeleted = false;

    /**
     * Constructor
     *
     * @param array $properties data
     * @param array $options
     * - name: Model name
     * - exists: if the model exists in the database (set during find)
     * - markClean: mark the entity as clean after creation. This is useful for when loading records
     * from the database.
     */
    public function __construct(array $properties = [], array $options = [])
    {
        $options += ['name' => null, 'exists' => false, 'markClean' => false];

        $this->entityName = $options['name'];
        $this->entityExists = $options['exists'];

        foreach ($properties as $property => $value) {
            $this->set($property, $value);
        }
        if ($options['markClean']) {
            $this->reset();
        }

        if (! empty($this->_hidden)) {
            deprecationWarning('Entity::$_hidden is deprecated use Entity::$hidden instead');
            $this->hidden = $this->_hidden;
        }
        if (! empty($this->_virtual)) {
            deprecationWarning('Entity::$_virtual is deprecated use Entity::$virtual instead');
            $this->virtual = $this->_virtual;
        }
    }

    /**
     * Sets a validation error
     *
     * @deprecated
     *
     * @param string $field
     * @param string $error
     * @return void
     */
    public function invalidate(string $field, string $error): void
    {
        deprecationWarning('Entity::invalidate deprecated use error instead');
        $this->error($field, $error);
    }

    /**
     * If the record exists in the database (is set by save)
     *
     * @param boolean $exists
     * @return boolean
     */
    public function exists(bool $exists = null): bool
    {
        return $this->setGetPersisted('entityExists', $exists);
    }

    /**
     * If the record is a newly created record
     *
     * @param boolean $created
     * @return boolean
     */
    public function created(bool $created = null): bool
    {
        return $this->setGetPersisted('entityCreated', $created);
    }
    /**
     * If the record was deleted
     *
     * @param boolean $deleted
     * @return boolean
     */
    public function deleted(bool $deleted = null): bool
    {
        return $this->setGetPersisted('entityDeleted', $deleted);
    }

    /**
     * Setter/Getter for persisted states
     *
     * @param string $var
     * @param boolean $value
     * @return bool
     */
    private function setGetPersisted(string $var, bool $value = null): bool
    {
        if ($value === null) {
            return $this->$var;
        }

        return $this->$var = $value;
    }

    /**
     * Checks if a entity has a property SET (regardless if null).
     *
     * @deprecated This will be deprecated as its code bloat
     *
     * @param string $property
     * @return bool
     */
    public function propertyExists(string $property): bool
    {
        deprecationWarning('Entity::propertyExists has been deprecated');

        return in_array($property, $this->properties());
    }

    /**
     * Converts this entity into JSON
     *
     * @param array $options Supported options are
     *   - pretty: default:false for json pretty print
     *
     * @return string
    */
    public function toJson(array $options = []): string
    {
        $options += ['pretty' => false];

        return json_encode($this->jsonSerialize(), $options['pretty'] ? JSON_PRETTY_PRINT : 0);
    }

    /**
     * Converts this entity into XML
     *
     * @return string
     */
    public function toXml(): string
    {
        $root = Inflector::camelCase($this->name() ?? 'record');

        return Xml::fromArray([$root => $this->toArray()]);
    }

    /**
     * Sets and gets hidden properties
     *
     * @deprecated This will likely be deprecated in future
     *
     * @param array $properties
     * @return array
     */
    public function hidden(array $properties = null): array
    {
        if ($properties === null) {
            return $this->hidden;
        }

        return $this->hidden = $properties;
    }

    /**
     * Sets and gets virtual properties
     *
     * @deprecated This will likely be deprecated in future
     *
     * @param array $properties
     * @return array
     */
    public function virtual(array $properties = null): array
    {
        if ($properties === null) {
            return $this->virtual;
        }

        return $this->virtual = $properties;
    }

    /**
     * ArrayAcces Interface for isset($entity);
     *
     * @param mixed $offset
     * @return bool result
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * ArrayAccess Interface for $entity[$offset];
     *
     * @param mixed $offset
     * @return mixed
     */
    public function &offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * ArrayAccess Interface for $entity[$offset] = $value;
     *
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * ArrayAccess Interface for unset($entity[$offset]);
     *
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        $this->unset($offset);
    }

    /**
     * JsonSerializable Interface for json_encode($entity). Returns the properties that will be
     * serialized as JSON
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
