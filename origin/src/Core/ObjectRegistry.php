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

namespace Origin\Core;

use Origin\Core\Exception\MissingClassException;

class ObjectRegistry
{
    protected $loaded = [];
    protected $enabled = [];

    public function &__get($name)
    {
        return $this->get($name);
    }

    public function __isset($name)
    {
        return isset($this->loaded[$name]);
    }

    /**
     * Calls a method on all ENABLED objects.
     *
     * @param string $method e.g startup
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

    public function clear()
    {
        unset($this->loaded);
        $this->loaded = [];
    }

    public function disable(string $object)
    {
        $key = array_search($object, $this->enabled);
        if ($key !== false) {
            unset($this->enabled[$key]);

            return true;
        }

        return false;
    }

    public function enable(string $object)
    {
        if (!isset($this->loaded[$object]) or in_array($object, $this->enabled)) {
            return false;
        }
        $this->enabled[] = $object;

        return true;
    }

    public function enabled()
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

    public function has(string $name)
    {
        return isset($this->loaded[$name]);
    }

    public function set(string $name, $object)
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
        $options += ['enable'=>true];

        $object = $this->create($name, $options);

        $this->set($name, $object);

        if ($options['enable']) {
            $this->enable($name);
        }

        return $object;
    }

    public function loaded()
    {
        return array_keys($this->loaded);
    }

    public function unload(string $name)
    {
        if (isset($this->loaded[$name])) {
            unset($this->loaded[$name]);

            return true;
        }

        return false;
    }

    /**
     * Frees the memory
     *
     * @return void
     */
    public function destroy()
    {
        foreach ($this->loaded as $name => $object) {
            $this->unload($name);
        }
        $this->clear();
        return null;
    }

    /**
     * This is will create the class, options will be passed for loaded for
     * extended classes to use if needed.
     *
     * @param string $class
     * @param array  $options
     *
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
     *
     * @return object
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
     *
     * @return string $namespacedClass
     */
    protected function className(string $class)
    {
        return Resolver::className($class);
    }

    protected function throwException(string $object)
    {
        throw new MissingClassException($object);
    }
}
