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
declare(strict_types = 1);
namespace Origin\Core;

use Origin\Core\Exception\MissingClassException;

class ObjectRegistry
{
    /**
     * Objects that have been loaded
     *
     * @var array
     */
    protected $loaded = [];

    /**
     * Enabled object list
     *
     * @var array
     */
    protected $enabled = [];

    /**
     * Magic get method
     *
     * @param string $name
     * @return mixed
     */
    public function &__get($name)
    {
        return $this->get($name);
    }

    /**
     * Magic isset method
     *
     * @param string $name
     * @return boolean
     */
    public function __isset($name)
    {
        return isset($this->loaded[$name]);
    }

    /**
     * Calls a method on all ENABLED objects.
     *
     * @param string $method e.g startup
     * @return void|\Origin\Http\Response
     */
    public function call(string $method, array $arguments = [])
    {
        foreach ($this->enabled as $name) {
            $object = $this->loaded[$name];
            if (method_exists($object, $method)) {
                call_user_func_array([$object, $method], $arguments);
            }
        }
    }

    /**
     * Clears the loaded items
     *
     * @return void
     */
    public function clear() : void
    {
        unset($this->loaded);
        $this->loaded = $this->enabled = [];
    }

    /**
     * Disables an object
     *
     * @param string $object
     * @return bool
     */
    public function disable(string $object) : bool
    {
        $key = array_search($object, $this->enabled);
        if ($key !== false) {
            unset($this->enabled[$key]);

            return true;
        }

        return false;
    }

    /**
     * Enables an object
     *
     * @param string $object
     * @return bool
     */
    public function enable(string $object) : bool
    {
        if (! isset($this->loaded[$object]) or in_array($object, $this->enabled)) {
            return false;
        }
        $this->enabled[] = $object;

        return true;
    }

    /**
     * Gets a list of enabled objefts
     *
     * @return array
     */
    public function enabled() : array
    {
        return $this->enabled;
    }

    /**
     * Added & to prevent Indirect modification of overloaded property errors. change nulled
     * to $value for Only variable references should be returned by reference errors. and referenced
     * result.
     */
    public function &get(string $name)
    {
        $value = null;
        if (isset($this->loaded[$name])) {
            $value = &$this->loaded[$name];
        }

        return $value;
    }

    /**
     * Checks if the object registry has an object
     *
     * @param string $name
     * @return boolean
     */
    public function has(string $name) : bool
    {
        return isset($this->loaded[$name]);
    }

    /**
     * Adds an object to the object registry
     *
     * @param string $name
     * @param mixed $object
     * @return void
     */
    public function set(string $name, $object) : void
    {
        if (isset($this->loaded[$name])) {
            $this->unload($name);
        }
        $this->loaded[$name] = $object;
    }

    /**
     * Undocumented function
     *
     * @param string $name
     * @param array $options
     * @return mixed
     */
    public function load(string $name, array $options = [])
    {
        if (isset($this->loaded[$name])) {
            return $this->loaded[$name];
        }
        $options += ['enable' => true];

        $object = $this->create($name, $options);

        $this->set($name, $object);

        if ($options['enable']) {
            $this->enable($name);
        }

        return $object;
    }

    /**
     * Gets a list of loaded objects
     *
     * @return array
     */
    public function loaded() : array
    {
        return array_keys($this->loaded);
    }

    /**
     * Unloads an object from the registry
     *
     * @param string $name
     * @return boolean
     */
    public function unload(string $name) : bool
    {
        if (isset($this->loaded[$name])) {
            unset($this->loaded[$name]);
            $this->disable($name);

            return true;
        }

        return false;
    }

    /**
     * Frees the memory
     *
     * @return void
     */
    public function destroy() : void
    {
        foreach ($this->loaded as $name => $object) {
            $this->unload($name);
        }
        $this->clear();
    }

    /**
     * This is will create the class, options will be passed for loaded for
     * extended classes to use if needed.
     *
     * @param string $name
     * @param array  $options
     * @return object
     */
    protected function create(string $name, array $options = [])
    {
        if (isset($options['className'])) {
            $name = $options['className']; // With Namespace
        }

        $className = $this->className($name);
       
        if ($className === null) {
            $this->throwException($name);
        }

        return $this->createObject($className, $options);
    }

    /**
     * Creates the object from the class.
     *
     * @param string $class
     * @param array  $options
     * @return mixed
     */
    protected function createObject(string $class, array $options = [])
    {
        return new $class($options);
    }

    /**
     * Gets the className through Resolver. Registries can overide to
     * set object type and suffixes type;.
     *
     * @param string $class
     * @return string|null $namespacedClass
     */
    protected function className(string $class) : ?string
    {
        return Resolver::className($class);
    }

    /**
     * Throws an excpetion
     *
     * @param string $object
     * @return void
     */
    protected function throwException(string $object) : void
    {
        throw new MissingClassException($object);
    }
}
