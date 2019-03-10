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

    public static function write($key = null, $value = null)
    {
        self::dot()->set($key, $value);
        if ($key === 'debug') {
            ini_set('display_errors', (int) $value);
        }
    }

    public static function read($key = null)
    {
        return self::dot()->get($key);
    }

    public static function check($key = null)
    {
        return self::dot()->has($key);
    }

    public static function delete($key = null)
    {
        return self::dot()->delete($key);
    }
}
