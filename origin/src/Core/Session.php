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

class Session
{
    public static function initialize()
    {
        if (PHP_SAPI != 'cli' and is_writable(TMP . DS . 'sessions')) {
            session_save_path(TMP . DS . 'sessions');
        }
        
        $timeout = 3600;
        if (Configure::has('Session.timeout')) {
            $timeout = Configure::read('Session.timeout');
        }
        if (!self::started()) {
            session_start();
        }
      
        if (Session::check('Session.lastActivity')) {
            if (time() - Session::read('Session.lastActivity') > $timeout) {
                Session::destroy();
                Session::initialize();
            }
        }
        Session::write('Session.lastActivity', time());
    }

    public static function write(string $key = null, $value = null)
    {
        $Dot = new Dot($_SESSION);
        $Dot->set($key, $value);
        if (strpos($key, '.') === false) {
            $_SESSION[$key] = $value;

            return true;
        }
        // Overwite session vars
        $data = $Dot->items();
        foreach ($data as $key => $value) {
            $_SESSION[$key] = $value;
        }
    }

    /**
     * Reads an item from the session
     *
     * @param [type] $key
     * @return mixed|null
     */
    public static function read(string $key = null)
    {
        $Dot = new Dot($_SESSION);
        if ($Dot->has($key)) {
            return $Dot->get($key);
        }

        return null;
    }

    public static function check(string $key = null)
    {
        $Dot = new Dot($_SESSION);

        return $Dot->has($key);
    }

    public static function delete(string $key = null)
    {
        $Dot = new Dot($_SESSION);
        if ($Dot->has($key)) {
            $Dot->delete($key);
            $_SESSION = $Dot->items();

            return true;
        }

        return false;
    }

    /**
     * Destroys the session.
     */
    public static function destroy()
    {
        if (!self::started()) {
            session_start();
        }
        if (PHP_SAPI !== 'cli') {
            session_destroy();
        }
        $_SESSION = [];
    }

    public static function started()
    {
        return isset($_SESSION) and session_id();
    }

    public static function id()
    {
        return session_id();
    }
}
