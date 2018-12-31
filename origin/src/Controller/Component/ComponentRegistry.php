<?php
/**
 * OriginPHP Framework
 * Copyright 2018 Jamiel Sharief.
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

use Origin\Core\ObjectRegistry;
use Origin\Controller\Controller;
use Origin\Core\Resolver;
use Origin\Controller\Component\Exception\MissingComponentException;

/**
 * A quick and easy way to create models and add them to registry. Not sure if
 * this will be added.
 */
class ComponentRegistry extends ObjectRegistry
{
    protected $controller = null;

    public function __construct(Controller $controller)
    {
        $this->controller = $controller;
    }

    protected function className($class)
    {
        return Resolver::className($class, 'Controller/Component');
    }

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
