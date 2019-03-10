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

namespace Origin\Core;

class Log
{
    /**
     * Writes to a log file.
     *
     * @param string $name
     * @param string $message   what you want to log
     * @param bool   $timestamp include timestamp
     */
    public static function write(string $name, string $message)
    {
        $filename = LOGS . DS . $name . '.log' ;
        $data  = '[' . date('Y-m-d G:i:s') . '] ' .  $message . "\n";
        return file_put_contents($filename, $data, FILE_APPEND | LOCK_EX);
    }
}
