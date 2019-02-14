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
    protected static $Dot = null;

    public static function init()
    {
        if (self::$Dot === null) {
            self::$Dot = new Dot();
        }
    }

    public static function write($key = null, $value = null)
    {
        self::init();

        self::$Dot->set($key, $value);

        if ($key == 'debug' and function_exists('ini_set')) {
            if ($value) {
                ini_set('display_errors', 1);
            } else {
                ini_set('display_errors', 0);
            }
        }
    }

    public static function read($key = null)
    {
        self::init();

        return self::$Dot->get($key);
    }

    public static function has($key = null)
    {
        self::init();

        return self::$Dot->has($key);
    }

    public static function delete($key = null)
    {
        self::init();

        return self::$Dot->delete($key);
    }
}
