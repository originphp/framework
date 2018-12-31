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

class Component
{
    /**
     * Holds a reference to the request object.
     *
     * @var object
     */
    protected $request = null;
    /**
     * Holds a reference to the registry object.
     *
     * @var object
     */
    protected $registry = null;

    /**
     * Holds the config.
     *
     * @var array
     */
    protected $config = null;

    /**
     * Default config used.
     *
     * @var array
     */
    protected $defaultConfig = [];

    /**
     * Holds a list of components that will be shared.
     *
     * @var array
     */
    public $uses = [];

    /**
     * Array of components and config.
     *
     * @var array
     */
    protected $components = [];

    public function __construct(Controller $controller, array $config = [])
    {
        $this->registry = $controller->registry;
        $this->request = $controller->request;

        $this->prepareComponents();
        $this->setDefaultConfig();
        $this->config($config);
        $this->initialize($config);
    }

    public function __get($name)
    {
        if (isset($this->components[$name])) {
            $this->{$name} = $this->registry->load($name, $this->components[$name]);

            if (isset($this->{$name})) {
                return $this->{$name};
            }
        }
    }

    protected function prepareComponents()
    {
        // Create map of components with config
        foreach ($this->uses as $component => $config) {
            if (is_int($component)) {
                $component = $config;
                $config = [];
            }
            $config = array_merge(['className' => $component.'Component'], $config);
            $this->components[$component] = $config;
        }
    }

    protected function setDefaultConfig()
    {
        if ($this->config === null) {
            $this->config = $this->defaultConfig;
        }
    }

    /**
     * Should be an array or null to get config.
     *
     * @param array $config
     *
     * @return array
     */
    public function config(array $config = null)
    {
        if (is_array($config)) {
            foreach ($config as $key => $value) {
                $this->config[$key] = $value;
            }
        }

        return $this->config;
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
     * This is called after the controller action.
     */
    public function shutdown()
    {
    }

    /**
     * Returns the controller
     * Dont want the controller in every component again, when we can use registry
     * which is referenced.
     */
    public function controller()
    {
        if ($this->registry) {
            return $this->registry->controller();
        }
    }
}
