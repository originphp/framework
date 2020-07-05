<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */
declare(strict_types=1);
namespace Origin\Publisher;

use Origin\Core\HookTrait;
use Origin\Core\ModelTrait;

class Listener
{
    use ModelTrait, HookTrait;

    public function __construct()
    {
        $this->executeHook('initialize');
    }

    /**
     * Dispatches a method
     *
     * @param string $method
     * @param array $arguments
     * @return boolean
     */
    public function dispatch(string $method, array $arguments = []): bool
    {
        $this->executeHook('startup');
        if ($this->executeHook($method, $arguments) === false) {
            return false;
        }
        $this->executeHook('shutdown');

        return true;
    }
}
