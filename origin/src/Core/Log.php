<?php
/**
 * OriginPHP Framework
 * Copyright 2018 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Log::write('debug',$message);.
 *
 * @var [type]
 */

namespace Origin\Core;

class Log
{
    /**
     * Writes to a log file.
     *
     * @param string $name      e.g debug,error, mysql
     * @param string $message   what you want to log
     * @param bool   $timestamp include timestamp
     */
    public static function write(string $name, string $message, bool $timestamp = true)
    {
        $filename = LOGS.DS.$name.'.log';

        if (!file_exists($filename)) {
            touch($filename);
            //chmod($filename, 0775);
        }

        $filehandle = fopen($filename, 'a+');
        if ($timestamp) {
            $message = self::format($message);
        }

        fwrite($filehandle, $message);
        fclose($filehandle);
    }

    public static function format($message)
    {
        $now = date('d/m/Y G:i:s');

        return "{$now} - {$message}\n";
    }
}
