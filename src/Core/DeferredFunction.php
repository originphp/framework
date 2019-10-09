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

/**
 * A defered function which is dispatched upon destruction
 */
class DeferredFunction
{
    /**
     * The callback to be executed
     *
     * @var callable
     */
    private $callback;

    /**
     * The arguments to be passed to the callback
     *
     * @var array
     */
    private $arguments = [];

    /**
     * @param callable $callback
     * @param array $arguments
     */
    public function __construct(callable $callback, array $arguments =[])
    {
        $this->callback = $callback;
        $this->arguments = $arguments;
    }

    /**
     * Dispatches the callback
     *
     * @return void
     */
    private function dispatch() : void
    {
        call_user_func($this->callback, ...$this->arguments);
    }

    public function __destruct()
    {
        $this->dispatch();
    }
}
