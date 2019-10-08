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
namespace Origin\Core;

trait CallbackRegistrationTrait
{
    /**
    * Holds the registered callbacks
    *
    * @var array
    */
    protected $registeredCallbacks = [];

    /**
     * Disabled callback list
     *
     * @var array
     */
    protected $disabledCallbacks = [];

    /**
     * Gets the registered callbacks
     *
     * @param string $type
     * @return array
     */
    public function registeredCallbacks(string $type = null) : array
    {
        if ($type === null) {
            return $this->registeredCallbacks;
        }

        return $this->registeredCallbacks[$type] ?? [];
    }

    /**
     * Disables a registered callback
     *
     * @param string $callbackOrCallbacks
     * @return bool
     */
    public function disableCallback(string $callback) : bool
    {
        $result = false;
        foreach ($this->registeredCallbacks as $type => $callbacks) {
            if (isset($callbacks[$callback])) {
                $this->disabledCallbacks[$callback][] = $callback;
                $result = true;
                break;
            }
        }

        return $result;
    }

    /**
     * Enabales a disabled callback
     *
     * @param string $callback
     * @return bool
     */
    public function enableCallback(string $callback) : bool
    {
        $result = false;
        if (isset($this->disabledCallbacks[$callback])) {
            unset($this->disabledCallbacks[$callback]);
            $result = true;
        }

        return $result;
    }
}
