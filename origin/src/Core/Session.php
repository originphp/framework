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

namespace Origin\Core;

class Session
{
    public static function init()
    {
        if (PHP_SAPI != 'cli' and is_writable(SESSIONS)) {
            session_save_path(SESSIONS);
        }
        $timeout = 3600;

        if ($timeout = Configure::read('Session.timeout')) {
            $timeout = Configure::read('Session.timeout');
        }
        session_start();
        $lastActivity = Session::read('Session.lastActivity');
        if ($lastActivity) {
            if (time() - $lastActivity > $timeout) {
                Session::destroy();
                Session::init();
            }
        }
        Session::write('Session.lastActivity', time());
    }

    public static function write($key = null, $value = null)
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

    public static function read($key = null)
    {
        $Dot = new Dot($_SESSION);
        if ($Dot->has($key)) {
            return $Dot->get($key);
        }

        return false;
    }

    public static function check($key = null)
    {
        $Dot = new Dot($_SESSION);

        return $Dot->has($key);
    }

    public static function delete($key = null)
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
        if (self::started()) {
            $cookieName = session_name();

            session_unset();
            if (isset($_COOKIE[$cookieName])) {
                unset($_COOKIE[$cookieName]);
            }
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
