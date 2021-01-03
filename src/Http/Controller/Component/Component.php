<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2021 Jamiel Sharief.
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
namespace Origin\Http\Controller\Component;

use Origin\Http\Request;
use Origin\Http\Response;
use Origin\Core\HookTrait;
use Origin\Http\Controller\Controller;
use Origin\Configurable\InstanceConfigurable as Configurable;

class Component
{
    use HookTrait;
    use Configurable;
    
    /**
     * Controller Object
     *
     * @var \Origin\Http\Controller\Controller
     */
    private $controller = null;

    /**
     * Array of components and config. This poupulated during construct and is used
     * for lazyloading.
     *
     * @var array
     */
    private $components = [];

    public function __construct(Controller $controller, array $config = [])
    {
        $this->controller = $controller;
 
        $this->config($config);
        $this->executeHook('initialize', [$config]);
    }

    /**
     * Loads a component, the component is not returned, but when you call it will be
     * lazy loaded
     *
     * examples:
     *
     * Session
     * MyPlugin.Session
     *
     * @param string $name
     * @param array $config
     * @return void
     */
    public function loadComponent(string $name, array $config = [])
    {
        list($plugin, $component) = pluginSplit($name);
        if (! isset($this->components[$component])) {
            $this->components[$component] = array_merge(['className' => $name . 'Component','enable' => false], $config);
        }
    }

    /**
     * Lazy loading
     */
    public function __get($name)
    {
        if (isset($this->components[$name])) {
            $this->$name = $this->controller->componentRegistry()->load($name, $this->components[$name]);
            
            if (isset($this->$name)) {
                return $this->$name;
            }
        }

        return null;
    }

    /**
     * Returns the controller
     * @return \Origin\Http\Controller\Controller
     */
    public function controller(): Controller
    {
        return $this->controller;
    }

    /**
     * Returns the request object
     *
     * @return \Origin\Http\Request
     */
    public function request(): Request
    {
        return $this->controller->request();
    }

    /**
     * Returns the response object
     *
     * @return \Origin\Http\Response
     */
    public function response(): Response
    {
        return $this->controller->response();
    }
}
