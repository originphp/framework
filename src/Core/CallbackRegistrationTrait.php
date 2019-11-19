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

declare(strict_types = 1);
namespace Origin\Core;

trait CallbackRegistrationTrait
{
    /**
     * @var array
     */
    protected $registeredCallbacks = [];

    /**
     * @var array
     */
    protected $disabledCallbacks = [];
    
    /**
     * Registers a callback
     *
     * @param string $callback
     * @param string $method
     * @param array $options
     * @return void
     */
    protected function registerCallback(string $callback, string $method, array $options = []) : void
    {
        $this->registeredCallbacks[$callback][$method] = $options;
    }

    /**
     * Disables a callback method
     *
     * @param string $method e.g. checkUser
     * @return boolean
     */
    protected function disableCallback(string $method) : bool
    {
        foreach ($this->registeredCallbacks as $callback => $registeredCallbacks) {
            if (isset($registeredCallbacks[$method])) {
                $this->disabledCallbacks[$method] = true;
                return true;
            }
        }
        return false;
    }

    /**
    * Enables a disabled callback method
    *
    * @param string $method
    * @return boolean
    */
    protected function enableCallback(string $method) : bool
    {
        $key = array_search($method, $this->disabledCallbacks);
        if ($key !== false) {
            unset($this->disabledCallbacks[$key]);
            return true;
        }
        return false;
    }

    /**
     * Gets all the enabled registeredCallbacks
     *
     * @param string $callback
     * @param boolean $disabled wether to get disabled callbacks as well
     * @return array
     */
    protected function registeredCallbacks(string $callback, bool $disabled = false) : array
    {
        $out = [];
        $registeredCallbacks = $this->registeredCallbacks[$callback] ?? [];
        foreach ($registeredCallbacks as $method => $options) {
            if ($disabled  === true or !isset($this->disabledCallbacks[$method])) {
                $out[$method] = $options;
            }
        }
        return $out;
    }
}
