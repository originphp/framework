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
namespace Origin\Http\Controller;

use ReflectionClass;
use ReflectionMethod;
use Origin\Http\Router;
use Origin\Model\Model;
use Origin\Http\Request;
use Origin\Http\Response;
use Origin\Core\HookTrait;
use Origin\Model\ModelTrait;
use Origin\Http\View\XmlView;
use Origin\Utility\Inflector;
use Origin\Http\View\JsonView;
use Origin\Core\InitializerTrait;
use App\Http\View\ApplicationView;
use Origin\Core\CallbackRegistrationTrait;
use Origin\Http\Controller\Component\Component;
use Origin\Http\Controller\Component\ComponentRegistry;
use Origin\Http\Controller\Exception\MissingMethodException;
use Origin\Http\Controller\Exception\PrivateMethodException;

/**
 * @property \Origin\Http\Controller\Component\PaginatorComponent $Paginator
 */
class Controller
{
    use ModelTrait, InitializerTrait, CallbackRegistrationTrait;
    use HookTrait;
    /**
     * Controller name.
     *
     * @var string
     */
    protected $name = null;

    /**
     * Model name for this controller.
     *
     * @var string
     */
    protected $modelName = null;

    /**
     * These are vars passed to view.
     *
     * @var array
     */
    protected $viewVars = [];

    /**
     * Automatically renders view.
     */
    protected $autoRender = true;

    /**
     * Default layout, set to false to not use a layout.
     *
     * @var string
     */
    protected $layout = 'default';

    /**
     * Holds the request object.
     *
     * @var \Origin\Http\Request
     */
    protected $request = null;

    /**
     * Holds the response object.
     *
     * @var \Origin\Http\Response
     */
    protected $response = null;

    /**
     * view helpers to load.
     * The core is default
     */
    protected $viewHelpers = [
        'Cookie' => ['className' => 'CookieHelper'],
        'Date' => ['className' => 'DateHelper'],
        'Flash' => ['className' => 'FlashHelper'],
        'Form' => ['className' => 'FormHelper'],
        'Html' => ['className' => 'HtmlHelper'],
        'Number' => ['className' => 'NumberHelper'],
        'Session' => ['className' => 'SessionHelper'],
    ];

    /**
       * Holds the componentregistry object.
       *
       * @var \Origin\Http\Controller\Component\ComponentRegistry
       */
    protected $componentRegistry = null;

    /**
     * Array keys to be serialized
     *
     * @var array
     */
    protected $serialize = [];

    /**
     * Paginator Settings.
     *
     * @var array (limit,order,fields,conditions)
     */
    protected $paginate = [];

    public function __construct(Request $request = null, Response $response = null)
    {
        list($namespace, $name) = namespaceSplit(get_class($this));
        $this->name = substr($name, 0, -10);

        $this->modelName = Inflector::singular($this->name);

        $this->request = $request ?: new Request();
        $this->response = $response ?: new Response();

        $this->componentRegistry = new ComponentRegistry($this);

        // Set default callbacks to be inline with framework
        $this->beforeAction('startup');
        $this->afterAction('shutdown');
  
        $this->executeHook('initialize');
        $this->initializeTraits();
    }

    /**
     * Lazyload core Components
     *
     * @param string $name
     * @return boolean
     */
    public function __isset($name)
    {
        if (in_array($name, ['Session','Cookie','Flash'])) {
            $this->$name = $this->loadComponent($name);

            return true;
        }

        return false;
    }
    /**
     * Loads a Component for use with the controller.
     *
     * @param string $name   Component name e.g. Auth
     * @param array  $config array of config to be passed to component. Class name
     * @return \Origin\Http\Controller\Component\Component
     */
    public function loadComponent(string $name, array $config = []) : Component
    {
        list($plugin, $component) = pluginSplit($name);
        $config = array_merge(['className' => $name.'Component'], $config);

        return $this->$component = $this->componentRegistry->load($name, $config);
    }

    /**
     * Loads a helper to be used in the View.
     *
     * @param string $name   Helper name e.g. Form
     * @param array  $config array of config to be passed to helper
     * @return void
     */
    public function loadHelper(string $name, array $config = []) : void
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
        // handle lazyloading
        if (isset($this->$name)) {
            if ($this->$name instanceof Model or $this->$name instanceof Component) {
                return $this->$name;
            }
        }
       
        return null;
    }

    /**
     * Checks if an action on this controller is accessible.
     *
     * @param string $action
     * @return bool
     */
    protected function isAccessible(string $action) : bool
    {
        $controller = new ReflectionClass(Controller::class);
        if ($controller->hasMethod($action)) {
            return false;
        }
        if (! method_exists($this, $action)) {
            return false;
        }
        $reflection = new ReflectionMethod($this, $action);

        return $reflection->isPublic();
    }

    /**
     * Sends a value or array of values to the view array.
     *
     * @param string|array $name key name or array
     * @param mixed $value if key is a string set the value for this
     * @return void
     */
    public function set($name, $value = null) : void
    {
        if (is_array($name)) {
            $data = $name;
        } else {
            $data = [$name => $value];
        }

        $this->viewVars = array_merge($this->viewVars, $data);
    }

    /**
     * The controller startup process
     *
     * @return \Origin\Http\Response|null
     */
    protected function startupProcess() : ?Response
    {
        if (! $this->triggerCallback('beforeAction')) {
            return $this->response;
        }
       
        if ($this->isResponseOrRedirect($this->componentRegistry->call('startup'))) {
            return $this->response;
        }

        return null;
    }
   
    /**
     * The controller shutdown process
     *
     * @return \Origin\Http\Response|null
     */
    protected function shutdownProcess()  : ?Response
    {
        if ($this->isResponseOrRedirect($this->componentRegistry->call('shutdown'))) {
            return $this->response;
        }
        if (! $this->triggerCallback('afterAction', true)) {
            return $this->response;
        }
        //# Free Mem for no longer used items
        $this->componentRegistry->destroy();
        unset($this->componentRegistry);

        return null;
    }

    /**
       * Triggers a callback, it always returns true unless a response or redirect
       * is detected.
       *
       * @param string $type
       * @return bool
       */
    protected function triggerCallback(string $type, bool $reverse = false) : bool
    {
        $callbacks = $this->registeredCallbacks($type);
        if ($reverse) {
            $callbacks = array_reverse($callbacks);
        }
        foreach ($callbacks as $callback => $options) {
            if (method_exists($this, $callback)) {
                if ($this->isResponseOrRedirect($this->$callback())) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Registers a before action callback
     *
     * @param string $method
     * @return void
     */
    public function beforeAction(string $method) : void
    {
        $this->registeredCallbacks['beforeAction'][$method] = [];
    }

    /**
     * Registers an after action callback
     *
     * @param string $method
     * @return void
     */
    public function afterAction(string $method) : void
    {
        $this->registeredCallbacks['afterAction'][$method] = [];
    }

    /**
     * Registers a beforeRender callback
     *
     * @param string $method
     * @return void
     */
    public function beforeRender(string $method) : void
    {
        $this->registeredCallbacks['beforeRender'][$method] = [];
    }

    /**
    * Registers a beforeRedirect callback
    *
    * @param string $method
    * @return void
    */
    public function beforeRedirect(string $method) : void
    {
        $this->registeredCallbacks['beforeRender'][$method] = [];
    }

    /**
    * Checks if the result is a response object or redirect was called
    *
    * @param mixed $result
    * @return boolean
    */
    private function isResponseOrRedirect($result) : bool
    {
        return ($result instanceof Response or $this->response->headers('Location'));
    }

    /**
     * Loads the PaginatorComponent and passes the settings to it.
     *
     * @param string $model name of the model
     * @param array $settings the settings used by PaginatorComponent these are the same settings as in
     * find query (fields, joins, order,limit, group, callbacks,contain)
     * @return mixed
     */
    public function paginate(string $model = null, array $settings = [])
    {
        if ($model === null) {
            $model = $this->modelName;
        }
    
        $object = $this->loadModel($model);

        $this->loadComponent('Paginator');
        if (! isset($this->viewHelpers['Paginator'])) {
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
     * ### View Types
     * - template - standard view
     * - xml:takes an array and coverts to xml
     * - json: takes an array of data and converts to json
     * - text: sends a txt response, this can be handy when dealing with ajax
     * - file: this loads an external file with file_get_contents. This does not send the file.
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
     *
     * @param string|array $options you can pass a string which is the template name this is the same
     * as the current action, if it starts with / then it will look in a different folder. If you pass
     * an array options, you can do so as follows:
     *   Types:
     *   - json: a string, array or object will be converted to json. E.g. $this->render(['json'=>$result]);
     *   - xml: an xml string or an array which will be converted to XML
     *   - text: For rendering plain text
     *   - file: a filename, and file get contents will be used.
     *   - template: default option, this will render the html template from the views folder.
     *   Other
     *   - status: the status code to return, e.g. 404
     * @return void
     */
    public function render($options = [])
    {
        $template = $this->request->params('action');
        $options = empty($options) ? $template : $options;
    
        if (is_string($options)) {
            $options = ['template' => $options];
        }
      
        $options += ['status' => $this->response->statusCode(), 'type' => 'html'];

        $body = null;

        /**
         * When working with json sometimes values can empty, for example autocomplete
         * so array key exists better than isset.
         */
        if (array_key_exists('json', $options)) {
            $this->renderJson($options['json'], $options['status']);

            return;
        }

        if ($this->autoRender and $this->serialize and $this->request->type() === 'json') {
            $this->renderJson(null, $options['status']);

            return;
        }
        
        if (array_key_exists('xml', $options)) {
            $this->renderXml($options['xml'], $options['status']);

            return;
        }

        if ($this->autoRender and $this->serialize and $this->request->type() === 'xml') {
            $this->renderXml(null, $options['status']);

            return;
        }
        
        if (array_key_exists('text', $options)) {
            $options['type'] = 'txt';
            $body = $options['text'];
        } elseif (array_key_exists('file', $options)) {
            $body = file_get_contents($options['file']);
            $options['type'] = mime_content_type($options['file']);
        }
        if ($body === null) {
            if (isset($options['template'])) {
                $template = $options['template'];
            }
            $view = new ApplicationView($this);
            $layout = null;
            if ($options['type'] === 'html' and $this->layout) {
                $layout = $this->layout;
            }
            $view->helpers($this->viewHelpers);
            $body = $view->render($template, $layout);
            unset($view);
        }
    
        $this->response->type($options['type']);   // 'json' or application/json
        $this->response->statusCode($options['status']); // 200
        $this->response->body($body); //
    }

    /**
     * Sets the key or keys of the viewVars to be serialized
     *
     * @param string|array $keyOrKeys
     * @return string|array
     */
    public function serialize($keyOrKeys = null)
    {
        if ($keyOrKeys === null) {
            return $this->serialize;
        }
        $this->serialize = $keyOrKeys;
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
    public function renderJson($data, int $status = 200) : void
    {
        $this->autoRender = false; // Only render once
        $this->triggerCallback('beforeRender');
        $this->response->type('json');   // 'json' or application/json
        $this->response->statusCode($status); // 200
        $this->response->body((new JsonView($this))->render($data));
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
    public function renderXml($data, int $status = 200) : void
    {
        $this->autoRender = false; // Disable for dispatcher
        $this->triggerCallback('beforeRender');
        $this->response->type('xml');
        $this->response->statusCode($status); // 200
        $this->response->body((new XmlView($this))->render($data));
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
     * @param int $code status code default 302
     * @return \Origin\Http\Response $response
     */
    public function redirect($url, int $code = 302) : Response
    {
        $this->autoRender = false;
        $this->triggerCallback('beforeRedirect');

        $this->response->statusCode($code);
        $this->response->header('Location', Router::url($url));
        $this->response->send();
        $this->response->stop();

        // Return the response object once called
        return $this->response;
    }

    /**
     * Returns the component registry
     *
     * @return \Origin\Http\Controller\Component\ComponentRegistry
     */
    public function componentRegistry() : ComponentRegistry
    {
        return $this->componentRegistry;
    }

    /**
     * Gets the viewVars
     *
     * @param array $viewVars
     * @return array
     */
    public function viewVars() : array
    {
        return $this->viewVars;
    }

    /**
     * Gets the Controller name
     *
     * @return string
     */
    public function name() : string
    {
        return $this->name;
    }

    /**
     * Gets the Controller Request Object
     *
     * @return \Origin\Http\Request
     */
    public function request() : Request
    {
        return $this->request;
    }

    /**
     * Gets the Controller Response Object
     *
     * @return \Origin\Http\Request
     */
    public function response() : Response
    {
        return $this->response;
    }

    /**
     * Dispatches the controller action
     *
     * @param string $action
     * @return \Origin\Http\Response
     *
     */
    public function dispatch(string $action) : Response
    {
        if (! method_exists($this, $action)) {
            throw new MissingMethodException([$this->name, $action]);
        }

        if (! $this->isAccessible($action)) {
            throw new PrivateMethodException([$this->name, $action]);
        }

        if ($this->isResponseOrRedirect($this->startupProcess())) {
            return $this->response;
        }
     
        call_user_func_array([$this, $action], $this->request->params('args'));
     
        if ($this->autoRender and $this->response->ready()) {
            $this->render();
        }

        return $this->shutdownProcess() ?: $this->response;
    }
}
