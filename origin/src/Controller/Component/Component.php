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
    protected $controller = null;


    /**
     * These are components that will be used within this component. Callbacks
     *
     * @var array
     */
    public $components = [];

    /**
     * Array of components and config. This poupulated by loadComponent
     *
     * @var array
     */
    protected $_components = [];

    public function __construct(Controller $controller, array $config = [])
    {
        $this->controller = $controller;
 
        $this->config($config);

        // Deal components within components
        foreach ($this->components as $name => $config) {
            if (is_int($name)) {
                $name = $config;
                $config = [];
            }
            list($plugin, $component) = pluginSplit($name);
            if (!isset($this->_components[$component])) {
                $this->_components[$component] =  array_merge(['className' => $name . 'Component'], $config);
            }
        }
      
        $this->initialize($config);
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
        return $this->controller;
    }

    /**
     * Returns the request object
     *
     * @return \Origin\Controller\Request
     */
    public function request()
    {
        return $this->controller->request();
    }

    /**
     * Returns the response object
     *
    * @return \Origin\Controller\Response
     */
    public function response()
    {
        return $this->controller->response();
    }

    /**
     * Returns a Logger Object
     *
     * @param string $channel
     * @return \Origin\Core\Logger
     */
    public function logger(string $channel = 'Component')
    {
        return $this->controller->logger($channel);
    }
}
