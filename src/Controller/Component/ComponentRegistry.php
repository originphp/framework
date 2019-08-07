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

namespace Origin\Controller\Component;

use Origin\Core\Resolver;
use Origin\Http\Response;
use Origin\Core\ObjectRegistry;
use Origin\Controller\Controller;
use Origin\Controller\Component\Exception\MissingComponentException;

/**
 * A quick and easy way to create models and add them to registry. Not sure if
 * this will be added.
 */
class ComponentRegistry extends ObjectRegistry
{
    /**
     * Holds the controller object
     *
     * @var \Origin\Controller\Controller
     */
    protected $controller = null;

    public function __construct(Controller $controller)
    {
        $this->controller = $controller;
    }

    protected function className(string $class)
    {
        return Resolver::className($class, 'Controller/Component');
    }

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

        return null;
    }

    /**
     * Undocumented function
     *
     * @param string $class
     * @param array $options
     * @return \Origin\Controller\Component\Component
     */
    protected function createObject(string $class, array $options = [])
    {
        return new $class($this->controller, $options);
    }

    protected function throwException(string $object)
    {
        throw new MissingComponentException($object);
    }

    public function controller()
    {
        return $this->controller;
    }
}
