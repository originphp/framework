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

use Origin\Core\Dot;

class Configure
{
    /**
     * Dot Object
     *
     * @var Dot
     */
    protected static $dot = null;

    /**
     * Returns the dot object
     *
     * @return Dot
     */
    protected static function dot()
    {
        if (self::$dot === null) {
            self::$dot = new Dot();
        }
        return self::$dot;
    }

    /**
     * Writes to global config
     *
     * @param string $key The key to use, accepts also dot notation e.g. Session.timeout
     * @param mixed $value The value to set
     * @return void
     */
    public static function write(string $key = null, $value = null)
    {
        self::dot()->set($key, $value);
        if ($key === 'debug') {
            ini_set('display_errors', (int) $value);
        }
    }

    /**
     * Reads from the global config
     *
     * @param string $key The key to read, accepts also dot notation e.g. Session.timeout
     * @return mixed
     */
    public static function read(string $key = null)
    {
        return self::dot()->get($key);
    }

    /**
     * Checks if a key exists on the gobal config
     *
     * @param string $key The key to check, accepts also dot notation e.g. Session.timeout
     * @return bool
     */
    public static function check(string $key = null) :bool
    {
        return self::dot()->has($key);
    }

    /**
     * Deletes a value from the gobal config
     *
     * @param string $key The key to use, accepts also dot notation e.g. Session.timeout
     * @return bool
     */
    public static function delete(string $key = null)
    {
        return self::dot()->delete($key);
    }
}
