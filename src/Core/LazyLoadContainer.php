<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright    Copyright (c) Jamiel Sharief
 * @link         https://www.originphp.com
 * @license      https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Core;

use Origin\Exception\Exception;

class LazyLoadContainer
{
    /**
     * Holds the config for this container
     *
     * @var array
     */
    protected $config = [];
    /**
     * Holds the objects for the entries
     *
     * @var array
     */
    protected $objects = [];

    /**
     * Adds an item
     *
     * @param string $alias The key to get the item by
     * @param string $className  The full class name including namespace
     * @param array $config An array of options that will be passed to the constructor
     * @return void
     */
    public function add(string $alias, string $className, array $config=[]){
        $this->config[$alias] = ['className'=>$className,'config'=>$config];
    }

    /**
     * Returns true if this container can return an item for the entry
     *
     * @param string $alias
     * @return boolean
     */
    public function has(string $alias){
        return isset($this->config[$alias]);
    }

    /**
     * Returns a list of items in this container
     *
     * @return void
     */
    public function list(){
        return array_keys($this->config);
    }

    /**
     * Gets an item from this container
     *
     * @param string $alias
     * @return mixed Entry
     */
    public function get(string $alias){
        if($this->has($alias)){
            if(empty($this->objects[$alias])){
                $className = $this->config[$alias]['className'];
                $this->objects[$alias] = new $className(...$this->config[$alias]['config']); 
            }
            return $this->objects[$alias];
        }
        throw new Exception(sprintf('%s was not found',$alias));
    }
}