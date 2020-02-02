<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
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
namespace Origin\Http\View;

use Origin\Http\Request;
use Origin\Http\Response;
use Origin\Core\HookTrait;
use Origin\Inflector\Inflector;
use Origin\Http\View\Helper\Helper;
use Origin\Core\Exception\Exception;
use Origin\Http\Controller\Controller;
use Origin\Http\View\Helper\HelperRegistry;
use Origin\Http\View\Exception\MissingViewException;
use Origin\Http\View\Exception\MissingLayoutException;
use Origin\Http\View\Exception\MissingElementException;

class View
{
    use HookTrait;

    /**
     * Name of controller that created this view.
     *
     * @var string
     */
    protected $controllerName = null;

    /**
     * These are the view vars (needed by testing).
     *
     * @var array
     */
    protected $vars = [];

    /**
     * This is the rendered view
     *
     * @var string
     */
    protected $content = null;

    /**
     * Request Object
     *
     * @var \Origin\Http\Request
     */
    protected $request = null;
    /**
     * Response Object
     *
     * @var \Origin\Http\Response
     */
    protected $response = null;

    /**
     * Holds the HelperRegistry object.
     *
     * @var \Origin\Http\View\Helper\HelperRegistry
     */
    protected $helperRegistry = null;

    /**
     * @var array
     */
    protected $helpers = [];

    /**
     * Root folder for views
     * @example /var/www/src/View
     * @var string
     */
    protected $viewPath = APP . DS . 'Http' . DS . 'View';

    public function __construct(Controller $controller = null)
    {
        $this->controllerName = $controller->name();

        $controller = $controller ?: new Controller();

        $this->request = $controller->request();
        $this->response = $controller->response();
        $this->vars = $controller->viewVars();
        
        $this->helperRegistry = new HelperRegistry($this);

        $this->executeHook('initialize');
    }

    /**
     * Sets or gets helpers
     *
     * @param array $vars
     * @return array
     */
    public function helpers(array $helpers = null) : array
    {
        if ($helpers === null) {
            return $this->helpers;
        }

        return $this->helpers = $helpers;
    }

    /**
     * Lazy load helpers.
     */
    public function __get($name)
    {
        if (isset($this->helpers[$name])) {
            return $this->helperRegistry()->load($name.'Helper', $this->helpers[$name]);
        }
        throw new Exception(sprintf('%sHelper is not loaded.', $name));
    }

    /**
     * Loads a helper
     *
     * @param string $name Helper name e.g Session, Cookie
     * @param array $config An array of config that you want to pass to the helper.
     * @return \Origin\Http\View\Helper\Helper
     */
    public function loadHelper(string $name, array $config = []) : Helper
    {
        list($plugin, $helper) = pluginSplit($name); // split so we can name properly
        $config = array_merge(['className' => $name . 'Helper'], $config);

        return $this->$helper = $this->helperRegistry()->load($name, $config);
    }

    /**
     * Returns a rendered element
     *
     * @param string $name Name of the element e.g. math-widget, html_editor
     * @param array $vars Variables that will be made available in the element
     * @return string
     */
    public function element(string $name, array $vars = []) : string
    {
        $element__filename = $this->getElementFilename($name);

        $vars = array_merge($this->vars, $vars);
       
        extract($vars);

        ob_start();

        include $element__filename;

        return ob_get_clean();
    }

    /**
     * Returns the rendered view
     *
     * @return string|null
     */
    public function content() : ?string
    {
        return $this->content;
    }

    /**
     * Returns the page title
     *
     * @return string|null
     */
    public function title() : ?string
    {
        return $this->vars['title'] ?? null;
    }

    /**
     * Gets a property value
     *
     * @param string $key Get view vars,contents,params
     * @return mixed
     */
    public function fetch(string $key)
    {
        return $this->$key ?? null;
    }

    /**
     * Wrapper for testing.
     *
     * @param string $filename
     * @return boolean
     */
    protected function fileExists(string $filename) : bool
    {
        return file_exists($filename);
    }

    /**
     * Gets a value from the view vars.
     *
     * @param string $key
     * @return mixed
     */
    public function get(string $key)
    {
        return $this->vars[$key] ?? null;
    }

    /**
     * Returns the helper registry object
     *
     * @return \Origin\Http\View\Helper\HelperRegistry
     */
    public function helperRegistry() : HelperRegistry
    {
        return $this->helperRegistry;
    }

    /**
     * Gets the filename for the element
     *
     * @param string $name
     * @return string
     */
    protected function getElementFilename(string $name) : string
    {
        $filename = $this->getFilename($name, 'Element');
        if ($this->fileExists($filename)) {
            return $filename;
        }

        throw new MissingElementException($name);
    }

    /**
     * Gets the view filename.
     *
     * @param string $name Template name e.g. controller_action, /Controller/action , Plugin.Controller/action
     * @return string filename
     */
    protected function getViewFilename(string $name) : string
    {
        $path = $this->getViewPath() . DS ;

        if ($name[0] === '/') {
            $path = $this->getViewPath(false); // get without controller folder
        } elseif (strpos($name, '.') !== false) {
            list($plugin, $name) = explode('.', $name);
            $path = PLUGINS . DS . Inflector::underscored($plugin) .  DS . 'src' . DS . 'Http' . DS .'View' . DS;
        }
         
        $filename = $path .  $name . '.ctp';
        if ($this->fileExists($filename)) {
            return $filename;
        }
 
        throw new MissingViewException([$this->controllerName, $name]);
    }

    /**
     * Gets the view path for the current request
     *
     * @param boolean $withControllerName
     * @return string
     */
    protected function getViewPath($withControllerName = true) : string
    {
        $viewPath = $this->viewPath;
        $plugin = $this->request->params('plugin');
        if ($plugin) {
            $viewPath = PLUGINS . DS . Inflector::underscored($plugin) . DS . 'src' . DS . 'Http' . DS . 'View';
        }
       
        if ($withControllerName) {
            $viewPath = $viewPath . DS . $this->controllerName;
        }

        return $viewPath;
    }

    /**
     * Gets the layout filename for a layout.
     *
     * @param string $layout default or Plugin.default;
     * @return string filename
     */
    protected function getLayoutFilename(string $layout) : string
    {
        $filename = $this->getFilename($layout, 'Layout');
        if ($this->fileExists($filename)) {
            return $filename;
        }

        throw new MissingLayoutException($layout);
    }

    /**
     * Used for determining layout/element filenames
     *
     * @param string $name
     * @param string $folder
     * @return string
     */
    protected function getFilename(string $name, string $folder) : string
    {
        list($plugin, $name) = pluginSplit($name);
        if ($plugin) {
            return PLUGINS . DS . Inflector::underscored($plugin) . DS . 'src' . DS . 'Http' .  DS . 'View' . DS . $folder . DS . $name . '.ctp';
        }

        return $this->viewPath . DS . $folder . DS . $name . '.ctp';
    }

    /**
     * renders the view.
     *
     * @param string $path index or Rest/json
     * @param string $layout
     * @return string $buffer
     */
    public function render(string $path, string $layout = null) : string
    {
        $view__filename = $this->getViewFilename($path);

        extract($this->vars);
        ob_start();
        require $view__filename;
        $buffer = $this->content = ob_get_clean();

        if ($layout) {
            $buffer = $this->renderLayout($layout);
        }

        $this->helperRegistry()->destroy();
        unset($this->helperRegistry);

        return $buffer;
    }

    protected function renderLayout(string $layout) : string
    {
        $layout_filename = $this->getLayoutFilename($layout);

        if (! isset($this->vars['title'])) {
            $this->vars['title'] = Inflector::human(Inflector::underscored($this->controllerName));
        }
       
        extract($this->vars);
        ob_start();
        require $layout_filename;

        return ob_get_clean();
    }

    /**
     * Adds a value to view vars.
     *
     * @param string $key
     * @param mixed  $value
     * @return void
     */
    public function set(string $key, $value) : void
    {
        $this->vars[$key] = $value;
    }

    /**
    * Gets the Response
    *
    * @return \Origin\Http\Response
    */
    public function response() : Response
    {
        return $this->response;
    }

    /**
     * Gets the request
     *
     * @return \Origin\Http\Request
     */
    public function request() : Request
    {
        return $this->request;
    }
}
