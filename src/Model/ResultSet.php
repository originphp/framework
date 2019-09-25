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
use ArrayAccess;

/**
 * A minimialist object to hold results from the query, standardize how single and multiple
 * records are handled
 *
 */
class ResultSet implements Iterator
{
    private $position = 0;

    private $data = null;

    public function __construct(array $data = [])
    {
        $this->position = 0;
        $this->data = $data;
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function current()
    {
        return $this->data[$this->position];
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
        return isset($this->array[$this->position]);
    }


    public function first()
    {
        return $this->current();
    }
    public function all()
    {
        return $this->data;
    }

    public function count()
    {
        return count($this->data);
    }


    /**
    * Magic method is trigged when calling var_dump
    *
    * @return array
    */
    public function __debugInfo()
    {
        return $this->data;
    }
}
