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
     * @return void
     */
    public function dispatch(array $arguments = []) : void
    {
        $this->executeHook('startup');
        (new CommandRunner())->run($arguments);
        $this->executeHook('shutdown');
    }
}
