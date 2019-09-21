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

namespace Origin\Model\Behavior;

use ReflectionMethod;
use Origin\Model\Model;
use ReflectionException;
use Origin\Core\Resolver;
use Origin\Core\ObjectRegistry;
use Origin\Model\Exception\MissingBehaviorException;

/**
 * A quick and easy way to create models and add them to registry. Not sure if
 * this will be added.
 */
class BehaviorRegistry extends ObjectRegistry
{
    /**
     * Model
     *
     * @var \Origin\Model\Model
     */
    protected $model = null;

    /**
     * Constructor
     *
     * @param Model $model
     * @return void
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Resolves the class name
     *
     * @param string $class
     * @return string|null $namespacedClass
     */
    protected function className(string $class) : ?string
    {
        return Resolver::className($class, 'Model/Behavior');
    }

    /**
     * Undocumented function
     *
     * @param string $class
     * @param array $options
     * @return \Origin\Model\Behavior\Behavior
     */
    protected function createObject(string $class, array $options = [])
    {
        return new $class($this->model, $options);
    }

    /**
     * Throws an exception
     *
     * @param string $object
     * @return void
     */
    protected function throwException(string $object) : void
    {
        throw new MissingBehaviorException($object);
    }

    /**
     * Looks for a behavior with the method
     *
     * @param string $method
     * @return \Origin\Model\Behavior
     */
    public function hasMethod(string $method)
    {
        foreach ($this->enabled as $name) {
            $object = $this->loaded[$name];
            try {
                $method = new ReflectionMethod($object, $method);
                if ($method->isPublic()) {
                    return $object;
                }
            } catch (ReflectionException $e) {
            }
        }
    }
}
