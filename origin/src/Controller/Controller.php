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

namespace Origin\Controller;

use Origin\Controller\Request;
use Origin\Controller\Response;
use Origin\Model\ModelRegistry;
use Origin\Model\Exception\MissingModelException;
use Origin\View\View;
use Origin\Controller\Component\ComponentRegistry;
use Origin\Core\Inflector;
use Origin\Core\Router;
use ReflectionClass;
use ReflectionMethod;

class Controller
{
    /**
     * Controller name.
     *
     * @var string
     */
    public $name = null;

    /**
     * Model name for this controller.
     *
     * @var string
     */
    public $modelName = null;

    /**
     * These are vars passed to view.
     *
     * @var array
     */
    public $viewVars = array();

    /**
     * Automatically renders view.
     */
    public $autoRender = true;

    /**
     * Default layout.
     *
     * @var string
     */
    public $layout = 'default';

    /**
     * Holds the request object.
     *
     * @var object
     */
    public $request = null;

    /**
     * Holds the response object.
     *
     * @var object
     */
    public $response = null;

    /**
     * Methods here are not reachable by a URL.
     *
     * @var array
     */
    public $privateMethods = array();

    /**
     * Helpers to load.
     */
    public $viewHelpers = array();

    /**
       * Holds the componentregistry object.
       *
       * @var ComponentRegistry
       */
    protected $componentRegistry = null;

    /**
     * Paginator Settings.
     *
     * @var array (limit,order,fields,conditions)
     */
    public $paginate = [];

    public function __construct(Request $request = null, Response $response = null)
    {
        // Get Controller Name
        if (isset($request->params['controller'])) {
            $this->name = $request->params['controller'];
        } else {
            list($namespace, $name) = namespaceSplit(get_class($this));
            $this->name = substr($name, 0, -10);
        }

        $this->modelName = Inflector::singularize($this->name);

        if ($request === null) {
            $request = new Request();
        }
        $this->request = $request;

        if ($response === null) {
            $response = new Response;
        }
        $this->response = $response;

        $this->componentRegistry = new ComponentRegistry($this);

        $this->initialize();
    }

    /**
     * Load multiple components to be used in this controller.
     *
     * @param array $components array of components
     */
    public function loadComponents(array $components)
    {
        $this->loadObjects($components, 'loadComponent');
    }

    /**
     * Load multiple helpers to be used in the view.
     *
     * @param array $components array of components
     */
    public function loadHelpers(array $helpers)
    {
        $this->loadObjects($helpers, 'loadHelper');
    }

    /**
     * Background function for loading multiple Components/Helpers.
     *
     * @param array  $objects array of components or helpers
     * @param string $method  which method to run them through
     */
    protected function loadObjects(array $objects, string $method)
    {
        foreach ($objects as $name => $config) {
            if (is_int($name)) {
                $name = $config;
                $config = [];
            }
            $this->{$method}($name, $config);
        }
    }

    /**
     * Loads a Component for use with the controller.
     *
     * @param string $name   Component name e.g. Auth
     * @param array  $config array of config to be passed to component. Class name
     */
    public function loadComponent(string $name, array $config = [])
    {
        list($plugin, $component) = pluginSplit($name);
        $config = array_merge(['className' => $name.'Component'], $config);
        $this->{$component} = $this->componentRegistry()->load($name, $config);
        return $this->{$component};
    }

    /**
     * Loads a helper to be used in the View.
     *
     * @param string $name   Helper name e.g. Form
     * @param array  $config array of config to be passed to helper
     */
    public function loadHelper(string $name, array $config = [])
    {
        list($plugin, $helper) = pluginSplit($name);
        $config = array_merge(['className' => $name.'Helper'], $config);
        $this->viewHelpers[$helper] = $config;
    }

    /**
     * Lazy load the model for this controler.
     */
    public function __get($name)
    {
        if ($name === $this->modelName) {
            return $this->loadModel($name);
        }

        return null;
    }

    /**
     * Loads a model, uses from registry or creates a new one.
     *
     * @param string $model
     * @params array $options
     *
     * @return Model
     */
    public function loadModel(string $model, array $options=[])
    {
        if (isset($this->{$model})) {
            return $this->{$model};
        }

        $this->{$model} = ModelRegistry::get($model, $options);

        if ($this->{$model}) {
            return $this->{$model};
        }
        throw new MissingModelException($model);
    }

    /**
     * Checks if an action on this controller is accessible.
     *
     * @param string $action
     *
     * @return bool
     */
    public function isAccessible(string $action)
    {
        $controller = new ReflectionClass('Origin\Controller\Controller');
        if ($controller->hasMethod($action)) {
            return false;
        }
        $reflection = new ReflectionMethod($this, $action);

        return $reflection->isPublic();
    }

    /**
     * This is immediately after construct method. Use this Hook load components,
     * helpers or anything that needs to be done when a new controller is created.
     */
    public function initialize()
    {
    }

    /**
     * Sends a value or array of values to the array.
     *
     * @param string|array $name key name or array
     * @param $value if key is a string set the value for this
     */
    public function set($name, $value = null)
    {
        if (is_array($name)) {
            $data = $name;
        } else {
            $data = [$name => $value];
        }

        $this->viewVars = array_merge($data, $this->viewVars);
    }

    /**
     * Callback before the action in the controller is called.
     */
    public function startup()
    {
    }

    /**
     * Callback after the action in the controller is called.
     */
    public function shutdown()
    {
    }

    public function startupProcess()
    {
        $this->startup();
        
        $this->componentRegistry()->call('startup');
    }

    public function shutdownProcess()
    {
        $this->componentRegistry()->call('shutdown');
        $this->shutdown();

        //# Free Mem for no longer used items
        $this->componentRegistry()->destroy();
        unset($this->componentRegistry);
    }

    /**
     * Loads the PaginatorComponent and passes the settings to it
     *
     * @param string $model name of the model
     * @param array $settings the settings used by PaginatorComponent these are the same settings as in
     * find query (fields, joins, order,limit, group, callbacks,recursive)
     * @return array paginated records
     */
    public function paginate(string $model = null, array $settings = [])
    {
        if ($model === null) {
            $model = $this->modelName;
        }

        $object = $this->loadModel($model);

        $this->loadComponent('Paginator');
        $this->loadHelper('Paginator');
        
        $defaults = $this->paginate;
        if (isset($this->paginate[$model])) {
            $defaults = $this->paginate[$model];
        }

        return $this->Paginator->paginate($object, $defaults + $settings);
    }

    /**
     * Renders a view. This is called automatically by the dispatcher.
     *
     * @param string $view index | /Rest/json | Plugin.Controller/action
     */
    public function render(string $view = null)
    {
        $this->autoRender = false; // Only render once

        if ($view === null) {
            $view = $this->request->params['action'];
        }
        $viewObject = new View($this);
        $body = $viewObject->render($view, $this->layout);
        $this->response->body($body);
    }

    /**
     * Redirects to a url, will disable autoRender but you should always
     * return $this->redirect to prevent code from running during tests etc
     *
     * @param string|array $url
     * @param int status code default 302
     * @return void
     */
    public function redirect($url, int $code = 302)
    {
        $this->autoRender = false;

        $this->response->statusCode($code);
        $this->response->header('Location', Router::url($url));
        $this->response->send();

        return $this->response;
    }
    
    public function componentRegistry()
    {
        return $this->componentRegistry;
    }
}
