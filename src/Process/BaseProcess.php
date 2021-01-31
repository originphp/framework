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
namespace Origin\Process;

abstract class BaseProcess
{
  
    /**
     * @return boolean
     */
    public function isTTY(): bool
    {
        return function_exists('posix_isatty') && posix_isatty(STDOUT);
    }
    
    /**
     * Checks if the process ended successfully
     *
     * @return boolean
     */
    public function success(): bool
    {
        return $this->exitCode() === 0;
    }

    abstract public function exitCode(): ? int;
}
