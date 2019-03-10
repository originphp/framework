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
use Origin\Core\ConfigTrait;
use Origin\Core\Logger;

class Helper
{
    use ConfigTrait;
    /**
     * Holds a reference to the view object.
     *
     * @var object
     */
    protected $view = null;


    /**
     * Array of helpers and config. This poupulated by loadHelper
     *
     * @var array
     */
    protected $_helpers = [];

    public function __construct(View $view, array $config = [])
    {
        $this->view = $view;
        
        $this->config($config);
        $this->initialize($config);
    }

    /**
     * Lazy loading
     */
    public function __get($name)
    {
        if (isset($this->_helpers[$name])) {
            $this->{$name} = $this->view()->helperRegistry()->load($name, $this->_helpers[$name]);
            
            if (isset($this->{$name})) {
                return $this->{$name};
            }
        }
    }

    /**
    * Sets another helper to be loaded within this helper. It will be
    * lazy loaded when needed, startup/stutdown callbacks will not be called when loading
    * helpers within helpers.
    *
    * @param string $helper e.g Auth, Flash
    * @param array $config
    * @return void
    */
    public function loadHelper(string $name, array $config = [])
    {
        list($plugin, $helper) = pluginSplit($name);
        $config = array_merge(['className' => $name . 'Helper'], $config);
        $this->_helpers[$helper] = $config;
    }

    /**
     * Loads Multiple helpers through the loadHelper method
     *
     * @param array $helpers
     * @return void
     */
    public function loadHelpers(array $helpers)
    {
        foreach ($helpers as $helper => $config) {
            if (is_int($helper)) {
                $helper = $config;
                $config = [];
            }
            $this->loadHelper($helper, $config);
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
     * Returns the view.
     */
    public function view()
    {
        return $this->view;
    }

    /**
     * Returns a Logger Object
     *
     * @param string $channel
     * @return Logger
     */
    public function logger(string $channel = 'Helper')
    {
        return new Logger($channel);
    }
}
