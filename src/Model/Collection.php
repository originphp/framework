<?php
declare(strict_types = 1);
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
namespace Origin\Model;

use Iterator;
use Countable;
use ArrayAccess;
use Origin\Utility\Xml;
use Origin\Utility\Inflector;

class Collection implements ArrayAccess, Iterator, Countable
{
    protected $items = null;
    protected $position = 0;
    protected $model = null;
    
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
    public function count()
    {
        return count($this->items);
    }

    /**
     * Returns an array of the collection items
     *
     * @return array
     */
    public function toArray()
    {
        return $this->convertToArray($this->items);
    }

    /**
     * Converts into Json
     * @see https://jsonapi.org/format/
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }

    /**
     * Converts into XML
     *
     * @return string
     */
    public function toXml()
    {
        $root = Inflector::camelCase(Inflector::plural($this->model ?? 'Record'));
        $data = [$root => [
            Inflector::camelCase($this->model ?? 'Record') => $this->toArray(),
        ]];

        return Xml::fromArray($data);
    }

    protected function convertToArray($results)
    {
        if ($results instanceof Entity) {
            return $results->toArray();
        }
        foreach ($results as $key => $value) {
            $results[$key] = $this->convertToArray($value);
        }

        return $results;
    }

    public function offsetExists($key)
    {
        return array_key_exists($key, $this->items);
    }
 
    public function offsetGet($key)
    {
        return $this->items[$key] ?? null;
    }

    public function offsetSet($key, $value)
    {
        if (is_null($key)) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }
    }
 
    public function offsetUnset($key)
    {
        unset($this->items[$key]);
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function current()
    {
        return $this->items[$this->position];
    }

    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        ++$this->position;
    }

    public function valid()
    {
        return isset($this->items[$this->position]);
    }
}
