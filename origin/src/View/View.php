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
namespace Origin\View;

/**
 * @todo change protected properties to have _prefix to get out of sight during
 * code completion views.
 */
use Origin\Controller\Controller;
use Origin\Core\Inflector;
use Origin\View\Helper\HelperRegistry;
use Origin\View\Exception\MissingViewException;
use Origin\View\Exception\MissingElementException;
use Origin\View\Exception\MissingLayoutException;
use Origin\Exception\Exception;

class View
{


    /**
     * Name of controller that created this view.
     */
    public $name = null;

    /**
     * These are the view vars (needed by testing).
     *
     * @var array
     */
    public $vars = [];

    /**
     * This is the rendered view
     *
     * @var string
     */
    protected $content = null;

    /**
     * Request Object
     *
     * @var \Origin\Controller\Request
     */
    public $request = null;
    /**
       * Response Object
       *
       * @var \Origin\Controller\Response
       */
    public $response = null;

    /**
     * Holds the HelperRegistry object.
     *
     * @var \Origin\View\Helper\HelperRegistry
     */
    protected $helperRegistry = null;

    /**
     * There
     *
     * @var array
     */
    protected $helpers = [];

    /**
     * Root folder for views
     * @example /var/www/src/View
     * @var string
     */
    protected $viewPath = SRC . DS . 'View';

    public function __construct(Controller $controller)
    {
        $this->name = $controller->name;

        $this->request =& $controller->request;
        $this->response =& $controller->response;
        $this->vars =& $controller->viewVars;

        $this->helperRegistry = new HelperRegistry($this);

        $this->helpers = $controller->viewHelpers;

        $this->initialize();
    }

    /**
     * Called during construct
     *
     * @return void
     */
    public function initialize()
    {
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
     * @return \Origin\View\Helper\Helper
     */
    public function loadHelper(string $name, array $config = [])
    {
        list($plugin, $helper) = pluginSplit($name); // split so we can name properly
        $config = array_merge(['className' => $name . 'Helper'], $config);
        $this->{$helper} = $this->helperRegistry()->load($name, $config);
        return $this->{$helper};
    }

    /**
     * Returns a rendered element
     *
     * @param string $name Name of the element e.g. math-widget, html_editor
     * @param array $vars Variables that will be made available in the element
     * @return string
     */
    public function element(string $name, array $vars = [])
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
    public function content()
    {
        return $this->content;
    }

    /**
     * Returns the page title
     *
     * @return string|null
     */
    public function title()
    {
        if (isset($this->vars['title'])) {
            return $this->vars['title'];
        }
        return null;
    }

    /**
     * Gets a property value
     *
     * @param string $key Get view vars,contents,params
     * @return mixed
     */
    public function fetch(string $key)
    {
        if (isset($this->{$key})) {
            return $this->{$key};
        }
        return null;
    }

    /**
     * Wrapper for testing.
     */
    protected function fileExists(string $filename)
    {
        return file_exists($filename);
    }

    /**
     * Gets a value from the view vars.
     *
     * @param string $key
     *
     * @return
     */
    public function get(string $key)
    {
        if (isset($this->vars[$key])) {
            return $this->vars[$key];
        }

        return null;
    }

    /**
     * Returns the helper registry object
     *
     * @return HelperRegistry
     */
    public function helperRegistry()
    {
        return $this->helperRegistry;
    }

    /**
     * Gets the filename for the element
     *
     * @param string $name
     * @return string
     */
    protected function getElementFilename(string $name)
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
    protected function getViewFilename(string $name)
    {
        $path = $this->getViewPath() . DS ;

        if ($name[0] === '/') {
            $path = $this->getViewPath(false); // get without controller folder
        } elseif (strpos($name, '.')!==false) {
            list($plugin, $name) = explode('.', $name);
            $path = PLUGINS . DS . Inflector::underscore($plugin) . '/src/View/';
        }
         
        $filename = $path .  $name . '.ctp';
       
        if ($this->fileExists($filename)) {
            return $filename;
        }

        throw new MissingViewException([$this->name, $name]);
    }

    /**
     * Gets the view path for the current request
     *
     * @param boolean $withControllerName
     * @return string
     */
    protected function getViewPath($withControllerName = true)
    {
        $viewPath = $this->viewPath;
        $plugin = $this->request->params('plugin');
        if ($plugin) {
            $viewPath = PLUGINS . DS . $plugin . DS . 'src' . DS . 'View';
        }
        if ($withControllerName) {
            $viewPath = $viewPath . DS . $this->name;
        }

        return $viewPath;
    }

    /**
     * Gets the layout filename for a layout.
     *
     * @param string $layout default or Plugin.default;
     *
     * @return string filename
     */
    protected function getLayoutFilename(string $layout)
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
    protected function getFilename(string $name, string $folder)
    {
        list($plugin, $name) = pluginSplit($name);
        if ($plugin) {
            return PLUGINS .DS . $plugin . DS . 'src' . DS . 'View' . DS . $folder . DS . $name . '.ctp';
        }
        return $this->viewPath . DS . $folder . DS . $name . '.ctp';
    }

    /**
     * renders the view.
     *
     * @param string $path index or Rest/json
     * @param array  $vars

     * @return string $buffer;
     */
    public function render(string $path, $layout = null)
    {
        $view__filename = $this->getViewFilename($path);

        extract($this->vars);
        ob_start();
        require $view__filename;
        $buffer = $this->content = ob_get_clean();

        if ($layout != null) {
            $buffer = $this->renderLayout($layout);
        }

        $this->helperRegistry()->destroy();
        unset($this->helperRegistry);

        return $buffer;
    }

    protected function renderLayout(string $layout)
    {
        $layout_filename = $this->getLayoutFilename($layout);

        if (!isset($this->vars['title'])) {
            $this->vars['title'] = Inflector::humanize(Inflector::underscore($this->name));
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
     */
    public function set(string $key, $value)
    {
        $this->vars[$key] = $value;
    }
}
