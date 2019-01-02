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

use Origin\Controller\Controller;
use Origin\Core\ConfigTrait;

class Component
{
    use ConfigTrait;
    /**
     * Holds a reference to the request object.
     *
     * @var object
     */
    protected $request = null;
    /**
     * Holds the componentregistry object.
     *
     * @var ComponentRegistry
     */
    protected $componentRegistry = null;

    /**
     * Array of components and config. This built during construct using
     * $components.
     *
     * @var array
     */
    protected $_components = [];

    public function __construct(Controller $controller, array $config = [])
    {
        $this->componentRegistry = $controller->componentRegistry();
        $this->request = $controller->request;
 
        $this->config($config);
        $this->initialize($config);
    }

    /**
     * Lazy loading
     */
    public function __get($name)
    {
        if (isset($this->_components[$name])) {
            $this->{$name} = $this->componentRegistry()->load($name, $this->_components[$name]);
       
            if (isset($this->{$name})) {
                return $this->{$name};
            }
        }
    }

    /**
    * Sets another component to be loaded within this component
    *
    * @param string $component e.g Auth, Flash
    * @param array $config
    * @return void
    */
    public function loadComponent(string $component, array $config = [])
    {
        $config = array_merge(['className' => $component.'Component'], $config);
        $this->_components[$component] = $config;
    }

    /**
     * Loads Multiple components through the loadComponent method
     *
     * @param array $components
     * @return void
     */
    public function loadComponents(array $components)
    {
        foreach ($components as $component => $config) {
            if (is_int($component)) {
                $component = $config;
                $config = [];
            }
            $this->loadComponent($component, $config);
        }
    }

    /**
     * This is called when component is loaded for the first time from the
     * controller.
     */
    public function initialize(array $config)
    {
    }

    /**
     * This called after the controller startup but before the controller action.
     */
    public function startup()
    {
    }

    /**
     * This is called after the controller action but before the controller shutdown
     */
    public function shutdown()
    {
    }

    /**
     * Returns the controller
     */
    public function controller()
    {
        return $this->componentRegistry()->controller();
    }

    /**
    * Gets the componentRegistry
    *
    * @return void
    */
    public function componentRegistry()
    {
        return $this->componentRegistry;
    }
}
