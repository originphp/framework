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

namespace Origin\Concern;

use Origin\Core\Resolver;
use Origin\Core\ObjectRegistry;
use Origin\Concern\Exception\MissingConcernException;

/**
 * A quick and easy way to create models and add them to registry. Not sure if
 * this will be added.
 */
class ConcernRegistry extends ObjectRegistry
{
    /**
     * Holds the module that this concerns
     *
     * @var object
     */
    protected $object;

    /**
     * Undocumented variable
     *
     * @var string $type Controller/Concern Model/Concern
     */
    protected $type = null;

    /**
     * Constructor
     *
     * @param object $module
     * @param string $type Controller/Concern Model/Concern
     */
    public function __construct(object $object, string $type = null)
    {
        $this->object = $object;
        $this->type = $type;
    }

    /**
     * Resolves the clas name
     *
     * @param string $class
     * @return string|null $namespacedClass
     */
    protected function className(string $class) : ?string
    {
        return Resolver::className($class, $this->type);
    }

    /**
     * Creates the Component object
     *
     * @param string $class
     * @param array $options
     * @return \Origin\Concern\Concern
     */
    protected function createObject(string $class, array $options = [])
    {
        return new $class($this->object, $options);
    }

    /**
     * Throws an exception
     *
     * @param string $object
     * @return void
     */
    protected function throwException(string $object) : void
    {
        throw new MissingConcernException($object);
    }

    /**
     * Returns the object
     *
     * @return mixed
     */
    public function object()
    {
        return $this->object;
    }

    /**
     * Looks for a concern with the method
     *
     * @param string $method
     * @return mixed
     */
    public function hasMethod(string $method)
    {
        foreach ($this->enabled as $name) {
            $object = $this->loaded[$name];
            if ($object->hasMethod($object, $method)) {
                return $object;
            }
        }
    }
}
