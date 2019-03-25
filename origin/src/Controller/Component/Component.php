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

use Origin\Controller\Controller;
use Origin\Core\ConfigTrait;

class Component
{
    use ConfigTrait;
    
    /**
     * Controller Object
     *
     * @var \Origin\Controller\Controller
     */
    protected $_controller = null;

    /**
     * Array of components and config. This poupulated during construct and is used
     * for lazyloading.
     *
     * @var array
     */
    protected $_components = [];

    public function __construct(Controller $controller, array $config = [])
    {
        $this->_controller = $controller;
 
        $this->config($config);
      
        $this->initialize($config);
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
    public function loadComponent(string $name, array $config=[])
    {
        list($plugin, $component) = pluginSplit($name);
        if (!isset($this->_components[$component])) {
            $this->_components[$component] =  array_merge(['className' => $name . 'Component','enable'=>false], $config);
        }
    }

    /**
     * Lazy loading
     */
    public function __get($name)
    {
        if (isset($this->_components[$name])) {
            $this->{$name} = $this->controller()->componentRegistry()->load($name, $this->_components[$name]);
            
            if (isset($this->{$name})) {
                return $this->{$name};
            }
        }
        return null;
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
     * @return \Origin\Controller\Controller
     */
    public function controller()
    {
        return $this->_controller;
    }

    /**
     * Returns the request object
     *
     * @return \Origin\Controller\Request
     */
    public function request()
    {
        return $this->_controller->request;
    }

    /**
     * Returns the response object
     *
    * @return \Origin\Controller\Response
     */
    public function response()
    {
        return $this->_controller->response;
    }

    /**
     * Returns a Logger Object
     *
     * @param string $channel
     * @return \Origin\Core\Logger
     */
    public function logger(string $channel = 'Component')
    {
        return $this->_controller->logger($channel);
    }
}
