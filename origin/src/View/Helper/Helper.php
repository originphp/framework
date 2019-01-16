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

namespace Origin\View\Helper;

use Origin\View\View;
use Origin\View\Templater;
use Origin\Core\ConfigTrait;

class Helper
{
    use ConfigTrait;
    /**
     * Holds a reference to the request object.
     *
     * @var object
     */
    protected $request = null;

    /**
         * Holds the HelperRegistry object.
         *
         * @var HelperRegistry
         */
    protected $helperRegistry = null;

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
        $this->helperRegistry = $view->helperRegistry();
        $this->request = $view->request;

        $this->prepareHelpers();

        $this->config($config);
        $this->initialize($config);
    }

    public function __get($name)
    {
        if (isset($this->helpers[$name])) {
            $this->{$name} = $this->helperRegistry()->load($name, $this->helpers[$name]);

            if (isset($this->{$name})) {
                return $this->{$name};
            }
        }
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
        return $this->helperRegistry()->view();
    }
}
