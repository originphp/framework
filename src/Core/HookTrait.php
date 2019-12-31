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

trait HookTrait
{
    /**
     * Executes a hook
     *
     * @param string $method
     * @param array $arguments
     * @return mixed any be anything or nothing
     */
    protected function executeHook(string $method, array $arguments = [])
    {
        if (is_callable([$this,$method])) {
            return call_user_func_array([$this,$method], $arguments);
        }
    }
}
