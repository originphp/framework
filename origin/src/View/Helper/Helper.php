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

namespace Origin\View\Helper;

use Origin\View\View;
use Origin\View\Templater;

class Helper
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
     * Other helpers that will be used.
     *
     * @var array
     */
    public $uses = [];

    /**
     * Array of helpers and config.
     *
     * @var array
     */
    protected $helpers = [];

    public function __construct(View $view, array $config = [])
    {
        $this->registry = $view->registry;
        $this->request = $view->request;

        $this->prepareHelpers();

        $this->config($config);
        $this->initialize($config);
    }

    public function __get($name)
    {
        if (isset($this->helpers[$name])) {
            $this->{$name} = $this->registry->load($name, $this->helpers[$name]);

            if (isset($this->{$name})) {
                return $this->{$name};
            }
        }
    }

    protected function prepareHelpers()
    {
        // Create map of helpers with config
        foreach ($this->uses as $helper => $config) {
            if (is_int($helper)) {
                $helper = $config;
                $config = [];
            }
            $config = array_merge(['className' => $helper.'Helper'], $config);
            $this->helpers[$helper] = $config;
        }
    }

    public function config(array $config = [])
    {
        if ($this->config === null) {
            $this->config = $this->defaultConfig;
        }
        $this->config = array_merge($this->config, $config);
    }

    /**
     * This is called when helper is loaded for the first time from the
     * controller.
     */
    public function initialize(array $config)
    {
    }

    /**
     * Creates a DOM id from field
     * Should be used by helpers to generate dom ids for fields.
     *
     * @param string $field
     *
     * @return string id
     */
    protected function domId(string $field)
    {
        return preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($field));
    }

    /**
     * Converts an array of attributes to string format e.g
     * becomes.
     *
     * @param array $attributes ['class'=>'form-control']
     *
     * @return string class="form-control"
     */
    protected function attributesToString(array $attributes = [])
    {
        $result = [];
        foreach ($attributes as $key => $value) {
            if ($value === true) {
                $result[] = $key;
            } else {
                $result[] = "{$key}=\"{$value}\"";
            }
        }

        if ($result) {
            return ' '.implode(' ', $result);
        }

        return '';
    }

    /**
     * Gets a templater instance.
     *
     * @return Templater
     */
    public function templater()
    {
        if (!isset($this->_templater)) {
            $this->_templater = new Templater();
        }

        return $this->_templater;
    }

    /**
     * Returns the view.
     */
    public function view()
    {
        if ($this->registry) {
            return $this->registry->view();
        }
    }
}
