<?php
/**
 * OriginPHP Framework
 * Copyright 2018 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright     Copyright (c) Jamiel Sharief
 *
 * @link          https://www.originphp.com
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\View;

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
    public $vars = array();

    /**
     * This is the rendered view without Layout (needed by testing).
     *
     * @var string
     */
    public $view = null;

    /**
     * This is the rendered view with layout (needed by testing).
     *
     * @var string
     */
    public $contents = null;

    /**
     * Request params. For access from view, @todo use __get to fetch
     * from request.
     */
    public $params = [];

    /**
     * Where the view files are.
     *
     * @var string
     */
    public $viewPath = null;

    public $request = null;

    public $response = null;

    /**
     * Holds the CSS block.
     *
     * @var string
     */
    public $css = '';

    /**
     * Holds the Javascripts block.
     *
     * @var string
     */
    public $scripts = '';

    /**
     * Registry of helpers.
     *
     * @var HelperRegistry
     */
    public $registry = null;

    protected $helpers = [];

    public function __construct(Controller $controller)
    {
        $this->name = $controller->name;

        $this->request = $controller->request;
        $this->response = $controller->reponse;
        $this->params = $controller->request->params;

        $this->vars = $controller->viewVars;

        $this->viewPath = $this->getViewPath();

        $this->registry = new HelperRegistry($this);

        $this->helpers = $controller->viewHelpers;
    }

    /**
     * Lazy load helpers.
     */
    public function __get($name)
    {
        if (isset($this->helpers[$name])) {
            return $this->registry->load($name.'Helper');
        }
        throw new Exception(sprintf('%sHelper is not loaded.', $name));
    }

    public function loadHelper(string $name, array $config = [])
    {
        $this->{$name} = $this->registry->load($name, $config);
    }

    protected function loadHelpers(array $helpers)
    {
        foreach ($helpers as $helper => $config) {
            $this->loadHelper($helper, $config);
        }
    }

    public function element(string $name, $vars = array())
    {
        $element__filename = $this->getElementFilename($name);

        $vars = array_merge($this->vars, $vars);

        extract($vars, EXTR_SKIP);

        ob_start();

        include $element__filename;

        return ob_get_clean();
    }

    /**
     * Gets a attribute value.
     *
     * @param string $key vars,conents,params
     *
     * @return
     */
    public function fetch(string $key)
    {
        if (isset($this->{$key})) {
            return $this->{$key};
        }
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

    protected function getElementFilename(string $name)
    {
        $path = VIEW.DS.'Element';
        list($plugin, $name) = pluginSplit($name);

        if ($plugin) {
            $path = PLUGINS.DS.$plugin.DS.'src'.DS.'View'.DS.'Element';
        }

        $filename = $path.DS.$name.'.ctp';

        if ($this->fileExists($filename)) {
            return $filename;
        }

        throw new MissingElementException($name);
    }

    /**
     * Gets the view filename.
     *
     * @param string $name action, /Folder/action
     *
     * @return string filename
     */
    protected function getViewFilename(string $name)
    {
        $path = $this->viewPath;

        if (strpos($name, '/') !== false) {
            $path = $this->getViewPath(false); // get without controller folder
        }

        $filename = $path.DS.$name.'.ctp';

        if ($this->fileExists($filename)) {
            return $filename;
        }

        throw new MissingViewException([$this->name, $name]);
    }

    protected function getViewPath($withControllerName = true)
    {
        $viewPath = VIEW;

        if (isset($this->params['plugin'])) {
            $viewPath = PLUGINS.DS.$this->params['plugin'].DS.'src'.DS.'View';
        }
        if ($withControllerName) {
            $viewPath .= DS.$this->name;
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
        $path = VIEW.DS.'Layout';
        list($plugin, $layout) = pluginSplit($layout);

        if ($plugin) {
            $path = PLUGINS.DS.$plugin.DS.'src'.DS.'View'.DS.'Layout';
        }

        $filename = $path.DS.$layout.'.ctp';

        if ($this->fileExists($filename)) {
            return $filename;
        }

        throw new MissingLayoutException($layout);
    }

    /**
     * renders the view.
     *
     * @param string $path index or Rest/json
     * @param array  $vars
     *
     * @return string $buffer;
     */
    public function render(string $path, $layout = null)
    {
        $view__filename = $this->getViewFilename($path);

        extract($this->vars, EXTR_SKIP);

        ob_start();

        require $view__filename;
        $buffer = $this->view = ob_get_clean();

        if ($layout != null) {
            $buffer = $this->renderLayout($layout);
        }

        //# Free Mem for no longer used items
        foreach ($this->registry->loaded() as $helper) {
            unset($this->{$helper});
        }
        $this->registry->clear();
        unset($this->registry);

        return $buffer;
    }

    protected function renderLayout(string $layout)
    {
        $layout_filename = $this->getLayoutFilename($layout);

        if (!isset($this->vars['title'])) {
            $this->vars['title'] = Inflector::humanize($this->name);
        }

        extract($this->vars, EXTR_SKIP);

        ob_start();

        require $layout_filename;

        $this->contents = ob_get_clean();

        return $this->contents;
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
