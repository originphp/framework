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
use Origin\Utility\Xml;
use Origin\Core\Logger;

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
    public $viewVars = [];

    /**
     * Automatically renders view.
     */
    public $autoRender = true;

    /**
     * Default layout, set to false to not use a layout.
     *
     * @var string
     */
    public $layout = 'default';

    /**
     * Holds the request object.
     *
     * @var \Origin\Controller\Request
     */
    public $request = null;

    /**
     * Holds the response object.
     *
     * @var \Origin\Controller\Response
     */
    public $response = null;

    /**
     * Helpers to load.
     */
    public $viewHelpers = [];

    /**
       * Holds the componentregistry object.
       *
       * @var \Origin\Controller\Component\ComponentRegistry
       */
    protected $componentRegistry = null;

    /**
     * Paginator Settings.
     *
     * @var array (limit,order,fields,conditions)
     */
    public $paginate = [];

    public function __construct(Request $request, Response $response)
    {
        list($namespace, $name) = namespaceSplit(get_class($this));
        $this->name = substr($name, 0, -10);

        $this->modelName = Inflector::singularize($this->name);

        $this->request = $request;
        $this->response = $response;

        $this->componentRegistry = new ComponentRegistry($this);

        $this->initialize();
    }

    /**
     * Loads a Component for use with the controller.
     *
     * @param string $name   Component name e.g. Auth
     * @param array  $config array of config to be passed to component. Class name
     * @return \Origin\Controller\Component\Component
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
     * @param array $options
     * @return \Origin\Model\Model
     */
    public function loadModel(string $model, array $options=[])
    {
        list($plugin, $alias) = pluginSplit($model);

        if (isset($this->{$alias})) {
            return $this->{$alias};
        }

        $this->{$alias} = ModelRegistry::get($model, $options);

        if ($this->{$alias}) {
            return $this->{$alias};
        }
        throw new MissingModelException($model);
    }

    /**
     * Checks if an action on this controller is accessible.
     *
     * @param string $action
     * @return bool
     */
    public function isAccessible(string $action)
    {
        $controller = new ReflectionClass(Controller::class);
        if ($controller->hasMethod($action)) {
            return false;
        }
        if (!method_exists($this, $action)) {
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
    public function beforeFilter()
    {
    }

    /**
     * Callback just prior to redirecting
     */
    public function beforeRedirect()
    {
    }
    /**
     * This is called after the startup, before shutdown
     */
    public function beforeRender()
    {
    }

    /**
     * Called after the controller action and the component shutdown function.
     * Remember to call parent
     *
     * @return void
     */
    public function afterFilter()
    {
    }

    public function startupProcess()
    {
        $this->beforeFilter();
        $this->componentRegistry()->call('startup');
    }

    public function shutdownProcess()
    {
        $this->componentRegistry()->call('shutdown');
        $this->afterFilter();

        //# Free Mem for no longer used items
        $this->componentRegistry()->destroy();
        unset($this->componentRegistry);
    }

    /**
     * Loads the PaginatorComponent and passes the settings to it.
     *
     * @param string $model name of the model
     * @param array $settings the settings used by PaginatorComponent these are the same settings as in
     * find query (fields, joins, order,limit, group, callbacks,contain)
     * @return array paginated records
     */
    public function paginate(string $model = null, array $settings = [])
    {
        if ($model === null) {
            $model = $this->modelName;
        }
    
        $object = $this->loadModel($model);

        $this->loadComponent('Paginator');
        if (!isset($this->viewHelpers['Paginator'])) {
            $this->loadHelper('Paginator');
        }
        
        $defaults = $this->paginate;
        if (isset($this->paginate[$model])) {
            $defaults = $this->paginate[$model];
        }

        return $this->Paginator->paginate($object, $defaults + $settings);
    }

    /**
     * Renders a view. This is called automatically by the dispatcher.
     *
     * If the argument is a string it will assume you want to load a standard
     * view using a template.
     *
     * $this->render('action');
     * $this->render('Controller/action')
     * $this->render('Plugin.Controller/action');
     *
     * // Set the type to render with data
     * $this->render(['xml'=>$array)
     * $this->render(['json'=>$array,'status'=>403]);
     * $this->render(['text'=>'OK']);
     *
     *
     * ### View Types
     * template - standard view
     * xml - takes an array and coverts to xml
     * json - takes an array of data and converts to json
     * text - sends a txt response, this can be handy when dealing with ajax
     * file - this loads an external file with file_get_contents, if it is not html then remember to set the content type. This
     * does send the file.
     *
     * ### Options
     * type: this is the content type that will be used (default:html). If use the xml or json options then the type will
     * be changed automatically. If you use file that is anything other than html then change the type
     * status: the status code to send. Most API providers use only a small subset of huge amount of
     * http error codes.
     *
     *  Here is set which should cover everything.
     *
     *  200 - OK (Success)
     *  400 - Bad Request (Failure - client side problem)
     *  500 - Internal Error (Failure - server side problem)
     *  401 - Unauthorized
     *  404 - Not Found
     *  403 - Forbidden (For application level permisions)
     */
    public function render($options=[])
    {
        $template = $this->request->params('action');
        if (empty($options)) {
            $options = $template;
        }
        if (is_string($options)) {
            $options = ['template'=>$options];
        }
 
        $options += [
            'status' => $this->response->status(),
            'type' => 'html'
        ];
        $body = null;

        if (!empty($options['json'])) {
            return $this->renderJson($options['json'], $options['status']);
        }
        
        if (!empty($options['xml'])) {
            return $this->renderXml($options['xml'], $options['status']);
        }
        
        if (!empty($options['text'])) {
            $options['type'] = 'text';
            $body = $options['text'];
        } elseif (!empty($options['file'])) {
            $body = file_get_contents($options['file']);
        }
        if ($body === null) {
            if (isset($options['template'])) {
                $template =  $options['template'];
            }
            $viewObject = new View($this);
            $body = $viewObject->render($template, $this->layout);
        }
    
        $this->response->type($options['type']);   // 'json' or application/json
        $this->response->status($options['status']); // 200
        $this->response->body($body); //
    }

    /**
     * Renders a json view
     *
     *  $this->renderJson([
     *     'data' => [
     *         'id' => 1234,'name'=>'James'
     *      ]
     *    ]);
     *
     *  $this->renderJson([
     *      'error' =>[
     *          'message' => 'Not Found','code' => 404
     *       ]
     *     ],404);
     *
     *  Most API providers use only a small subset of massiave amount of http error codes
     *
     *  These are the most important ones if you don't want to overcomplicate
     *
     *  200 - OK (Success)
     *  400 - Bad Request (Failure - client side problem)
     *  500 - Internal Error (Failure - server side problem)
     *  401 - Unauthorized
     *  404 - Not Found
     *  403 - Forbidden (For application level permisions)
     *
     * @param array|string $data data which will be json encoded
     * @return void
     */
    public function renderJson($data, int $status = 200)
    {
        $this->autoRender = false; // Only render once
        $this->beforeRender();
        
        if (is_object($data) and method_exists($data, 'toJson')) {
            $body = $data->toJson();
        } else {
            $body = json_encode($data);
        }
        $this->response->type('json');   // 'json' or application/json
        $this->response->status($status); // 200
        $this->response->body($body); //
    }

    /**
     * Renders an XML view using an array.
     *
     *  $this->renderXml([
     *       'post' => [
     *           '@category' => 'how tos', // to set attribute use @
     *           'id' => 12345,
     *           'title' => 'How to create an XML block',
     *           'body' =>  Xml::cdata('A quick brown fox jumps of a lazy dog.'),
     *           'author' => [
     *              'name' => 'James'
     *            ]
     *          ]
     *     ]);
     *
     * @param array $data
     * @param integer $status
     * @return void
     */
    public function renderXml($data, int $status = 200)
    {
        $this->autoRender = false; // Disable for dispatcher
        $this->beforeRender();
        
        if (is_object($data) and method_exists($data, 'toXml')) {
            $body = $data->toXml();
        } elseif (is_array($data)) {
            $body = Xml::fromArray($data);
        } else {
            $body = $data;
        }
        $this->response->type('xml');   // 'xml' or application/xml
        $this->response->status($status); // 200
        $this->response->body($body);
    }

    /**
     * Redirects to a url, will disable autoRender but you should always
     * return $this->redirect to prevent code from running during tests etc
     *
     * # Options
     * - controller
     * - action
     * - ? : query
     * - # : fragment
     *
     * @param string|array $url
     * @param int status code default 302
     * @return void
     */
    public function redirect($url, int $code = 302)
    {
        $this->autoRender = false;
        $this->beforeRedirect();

        $this->response->status($code);
        $this->response->header('Location', Router::url($url));
        $this->response->send();
        $this->response->stop();
    }

    /**
     * Returns the component registry
     *
     * @return \Origin\Controller\Component\ComponentRegistry
     */
    public function componentRegistry()
    {
        return $this->componentRegistry;
    }

    /**
     * Returns a Logger Object
     *
     * @param string $channel
     * @return \Origin\Core\Logger
     */
    public function logger(string $channel = 'Controller')
    {
        return new Logger($channel);
    }
}
