<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2021 Jamiel Sharief.
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

/**
 * This has been rewritten to be faster and leaner
 */
trait CallbacksTrait
{
    /**
     * @var array
     */
    private $enabledCallbacks = [];

    /**
     * @var array
     */
    private $disabledCallbacks = [];

    /**
     * Registers a callback
     *
     * @param string $name
     * @param string $method
     * @param array $options
     * @return void
     */
    protected function registerCallback(string $name, string $method, array $options = []): void
    {
        $this->enabledCallbacks[$name][$method] = $options;
    }

    /**
     * Disables a callback
     *
     * @param string $name
     * @param string $method
     * @return bool
     */
    protected function disableCallback(string $name, string $method = null): bool
    {
        /**
         * Backwards comptability
         * @deprecated This will be removed in 4.x
         */
        if (func_num_args() === 1) {
            deprecationWarning('Disabling callbacks must now supply the callback name and method.');

            return $this->backwardsDisable($name);
        }
        if (isset($this->enabledCallbacks[$name][$method])) {
            $this->disabledCallbacks[$name][$method] = $this->enabledCallbacks[$name][$method];
            unset($this->enabledCallbacks[$name][$method]);

            return true;
        }

        return false;
    }

    /**
     * Enables a callback that was previously disabled
     *
     * @param string $name
     * @param string $method
     * @return bool
     */
    protected function enableCallback(string $name, string $method = null): bool
    {
        /**
         * Backwards comptability
         * @deprecated This will be removed in 4.x
         */
        if (func_num_args() === 1) {
            deprecationWarning('Enabling callbacks must now supply the callback name and method.');

            return $this->backwardsEnable($name);
        }
        if (isset($this->disabledCallbacks[$name][$method])) {
            $this->enabledCallbacks[$name][$method] = $this->disabledCallbacks[$name][$method];
            unset($this->disabledCallbacks[$name][$method]);

            return true;
        }

        return false;
    }

    /**
     * Gets the active callbacks for this object, callbacks that were disabled will not be returned.
     *
     * @param string $name
     * @return array
     */
    protected function getCallbacks(string $name): array
    {
        return $this->enabledCallbacks[$name] ?? [];
    }

    /**
     * Dispatches a callback
     *
     * @param string $callback
     * @param array $arguments
     * @param boolean $cancelable
     * @return boolean
     */
    protected function dispatchCallback(string $callback, array $arguments = [], bool $cancelable = true): bool
    {
        foreach ($this->enabledCallbacks[$callback] ?? [] as $method => $options) {
            if ($this->$method(...$arguments) === false && $cancelable) {
                return false;
            }
        }

        return true;
    }

    /**
    *
    * @deprecated 4.x
    * @param string $method
    * @return bool
    */
    private function backwardsDisable(string $method): bool
    {
        $result = false;
        foreach (array_keys($this->enabledCallbacks) as $callback) {
            if (isset($this->enabledCallbacks[$callback][$method])) {
                $this->disableCallback($callback, $method);
                $result = true;
            }
        }

        return $result;
    }

    /**
     *
     * @deprecated 4.x
     * @param string $method
     * @return bool
     */
    private function backwardsEnable(string $method): bool
    {
        $result = false;
        foreach (array_keys($this->disabledCallbacks) as $callback) {
            if (isset($this->disabledCallbacks[$callback][$method])) {
                $this->enableCallback($callback, $method);
                $result = true;
            }
        }

        return $result;
    }

    /**
     * Use getCallbacks instead
     * @codeCoverageIgnore
     * @deprecated This will be removed in 4.x
     * @param string $callback
     * @return array
     */
    protected function registeredCallbacks(string $callback): array
    {
        deprecationWarning('RegisteredCallbacks has been deprecated use getCallbacks instead.');

        return $this->getCallbacks($callback);
    }

    /**
     * @codeCoverageIgnore
     * @deprecated This will be removed in 4.x
     * @param string $callback
     * @param array $arguments
     * @param boolean $cancelable
     * @return void
     */
    private function dispatchCallbacks(string $callback, array $arguments = [], bool $cancelable = true)
    {
        deprecationWarning('dispatchCallbacks has been deprecated use dispatchCallback instead.');

        return $this->dispatchCallback($callback, $arguments, $cancelable);
    }
}
