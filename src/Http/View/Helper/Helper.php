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

namespace Origin\Http\View\Helper;

use Origin\Http\Request;
use Origin\Http\Response;
use Origin\Core\HookTrait;
use Origin\Http\View\View;
use Origin\Core\ConfigTrait;

class Helper
{
    use ConfigTrait, HookTrait;
    /**
       * View Object
       *
       * @var \Origin\Http\View\View
       */
    protected $_view = null;

    /**
     * Array of helpers and config. This poupulated by loadHelper
     *
     * @var array
     */
    protected $_helpers = [];

    public function __construct(View $view, array $config = [])
    {
        $this->_view = $view;
        
        $this->config($config);
        $this->executeHook('initialize', [$config]);
    }

    /**
     * Loads a helper -  the helper is not returned, but when you call it will be
     * lazy loaded
     *
     * @param string $name
     * @param array $config
     * @return void
     */
    public function loadHelper(string $name, array $config = []) : void
    {
        list($plugin, $helper) = pluginSplit($name);
        if (! isset($this->_helpers[$helper])) {
            $this->_helpers[$helper] = array_merge(['className' => $name . 'Helper'], $config);
        }
    }

    /**
     * Handles the lazyloading
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if (isset($this->_helpers[$name])) {
            $this->$name = $this->view()->helperRegistry()->load($name, $this->_helpers[$name]);
            
            if (isset($this->$name)) {
                return $this->$name;
            }
        }
    }

    /**
     * Creates a DOM id from field
     * Should be used by helpers to generate dom ids for fields.
     *
     * @param string $field
     * @return string id
     */
    protected function domId(string $field) : string
    {
        return preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($field));
    }

    /**
     * Converts an array of attributes to string format e.g
     * becomes.
     *
     * @param array $attributes ['class'=>'form-control']
     * @return string class="form-control"
     */
    protected function attributesToString(array $attributes = []) : string
    {
        $result = [];
        foreach ($attributes as $key => $value) {
            if ($value === true) {
                $result[] = $key;
            } elseif (is_scalar($value)) {
                $result[] = "{$key}=\"{$value}\"";
            }
        }

        if ($result) {
            return ' '.implode(' ', $result);
        }

        return '';
    }

    /**
     * Returns the View
    *
    * @return \Origin\Http\View\View
    */
    public function view() : View
    {
        return $this->_view;
    }

    /**
     * Returns the request object
     *
     * @return \Origin\Http\Request
     */
    public function request() : Request
    {
        return $this->_view->request();
    }

    /**
     * Returns the response object
     *
     * @return \Origin\Http\Response
     */
    public function response() : Response
    {
        return $this->_view->response();
    }
}
