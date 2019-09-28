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

namespace Origin\Http\Controller\Component;

use Origin\Core\Resolver;
use Origin\Http\Response;
use Origin\Core\ObjectRegistry;
use Origin\Http\Controller\Controller;
use Origin\Http\Controller\Component\Exception\MissingComponentException;

/**
 * A quick and easy way to create models and add them to registry. Not sure if
 * this will be added.
 */
class ComponentRegistry extends ObjectRegistry
{
    /**
     * Holds the controller object
     *
     * @var \Origin\Http\Controller\Controller
     */
    protected $controller = null;

    /**
     * Constructor
     *
     * @param \Origin\Http\Controller\Controller $controller
     */
    public function __construct(Controller $controller)
    {
        $this->controller = $controller;
    }

    /**
     * Resolves the clas name
     *
     * @param string $class
     * @return string|null $namespacedClass
     */
    protected function className(string $class) : ?string
    {
        return Resolver::className($class, 'Controller/Component', null, 'Http');
    }

    /**
     * Calls a method on objects loaded, if one of those objects returns a response or has set the header location
     * then it will return the result
     *
     * @param string $method
     * @param array $arguments
     * @return \Origin\Http\Response|void
     */
    public function call(string $method, array $arguments = [])
    {
        foreach ($this->enabled as $name) {
            $object = $this->loaded[$name];
            if (method_exists($object, $method)) {
                $result = call_user_func_array([$object, $method], $arguments);
                // Redirect has been called
                if ($result instanceof Response or $this->controller->response->headers('Location')) {
                    return $result;
                }
            }
        }
    }

    /**
     * Creates the Component object
     *
     * @param string $class
     * @param array $options
     * @return \Origin\Http\Controller\Component\Component
     */
    protected function createObject(string $class, array $options = [])
    {
        return new $class($this->controller, $options);
    }

    /**
     * Throws an exception
     *
     * @param string $object
     * @return void
     */
    protected function throwException(string $object) : void
    {
        throw new MissingComponentException($object);
    }

    /**
     * Returns the controller object
     *
     * @return \Origin\Http\Controller\Controller
     */
    public function controller() : Controller
    {
        return $this->controller;
    }
}
