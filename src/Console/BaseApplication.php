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
declare(strict_types = 1);
namespace Origin\Console;

use Origin\Core\HookTrait;

class BaseApplication
{
    use HookTrait;

    public function __construct()
    {
        $this->executeHook('initialize');
    }

    /**
     * Dispatches the command
     *
     * @return int
     */
    public function dispatch(array $arguments = []): int
    {
        $this->executeHook('startup');
        $exitCode = (new CommandRunner())->run($arguments);
        $this->executeHook('shutdown');

        return $exitCode;
    }
}
