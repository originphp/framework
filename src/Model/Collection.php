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

use Countable;
use ArrayAccess;
use ArrayIterator;
use Origin\Xml\Xml;
use JsonSerializable;
use IteratorAggregate;
use Origin\Inflector\Inflector;

class Collection implements ArrayAccess, IteratorAggregate, Countable, JsonSerializable
{
    /**
     * Items in collection
     *
     * @var array
     */
    protected $items;

    /**
     * Name of model
     *
     * @var string|null
     */
    protected $model;
    
    public function __construct(array $items, array $options = [])
    {
        $options += ['name' => null];
        $this->model = $options['name'];
        $this->items = $items;
    }

    /**
     * Magic method is trigged when calling var_dump
     *
     * @return array
     */
    public function __debugInfo()
    {
        return $this->items;
    }

    /**
    * Counts the number of items in the collection
    *
    * @return int
    */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Returns true if the Collection is empty
     *
     * @return boolean
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * Returns an array of the collection items
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->convertToArray($this->items);
    }

    /**
     * Converts this collection into JSON
     *
     * @see https://jsonapi.org/format/
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->jsonSerialize());
    }

    /**
     * Converts this collection into XML
     *
     * @return string
     */
    public function toXml(): string
    {
        $root = Inflector::camelCase(Inflector::plural($this->model ?? 'Record'));
        $data = [$root => [
            Inflector::camelCase($this->model ?? 'Record') => $this->toArray(),
        ]];

        return Xml::fromArray($data);
    }

    /**
     * Converts results into an array
     *
     * @param \Origin\Model\Entity|array $results
     * @return array
     */
    protected function convertToArray($results): array
    {
        if ($results instanceof Entity) {
            return $results->toArray();
        }
        foreach ($results as $key => $value) {
            $results[$key] = $this->convertToArray($value);
        }

        return $results;
    }

    /**
     * ArrayAcces Interface for isset($collection);
     *
     * @param mixed $key
     * @return bool result
     */
    public function offsetExists($key)
    {
        return array_key_exists($key, $this->items);
    }
 
    /**
     * ArrayAccess Interface for $collection[$key];
     *
     * @param mixed $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->items[$key] ?? null;
    }

    /**
     * ArrayAccess Interface for $collection[$key] = $value;
     *
     * @param mixed $key
     * @param mixed $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        if (is_null($key)) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }
    }
 
    /**
     * ArrayAccess Interface for unset($collection[$key]);
     *
     * @param mixed $key
     * @return void
     */
    public function offsetUnset($key)
    {
        unset($this->items[$key]);
    }
 
    /**
     * IteratorAggregate Interface
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Gets the first item in the collection
     *
     * @return void
     */
    public function first()
    {
        $item = reset($this->items);

        return $item ? $item : null;
    }

    /**
     * JsonSerializable Interface for json_encode($collection). Returns the properties that will be serialized as
     * json
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
