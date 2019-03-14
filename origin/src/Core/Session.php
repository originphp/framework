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
use Origin\Exception\Exception;

class Session
{
    protected $started = false;

    protected $cli = false;

    public function __construct()
    {
        $this->cli = (PHP_SAPI === 'cli');

        if ($this->started() === false) {
            $this->start();
        }
        // For DOT
        if (!isset($_SESSION)) {
            $_SESSION = [];
        }
    }

    public function start()
    {
        if ($this->started) {
            return false;
        }

        if ($this->cli) {
            return $this->started = true;
        }

        if ($this->started() === PHP_SESSION_ACTIVE) {
            throw new Exception('Session alredy started.');
        }

        session_save_path(TMP . DS . 'sessions');

        if (!session_start()) {
            throw new Exception('Error starting a session.');
        }
     
        $this->started = true;

        if ($this->timedOut()) {
            $this->destroy();
            $this->start();
        }
    }

    /**
     * Checks if session timedout
     *
     * @param integer $timeout
     * @return boolean
     */
    protected function timedOut($timeout = 3600) : bool
    {
        if (Configure::check('Session.timeout')) {
            $timeout = Configure::read('Session.timeout');
        }
        $lastActivity = $this->read('Session.lastActivity');
        $this->write('Session.lastActivity', time());

        $result = false;
        if ($lastActivity) {
            $result = (time() - $lastActivity > $timeout);
        }
        
        return $result;
    }

    public function write(string $key = null, $value = null)
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
    public function read(string $key = null)
    {
        $Dot = new Dot($_SESSION);
        if ($Dot->has($key)) {
            return $Dot->get($key);
        }

        return null;
    }

    public function check(string $key = null)
    {
        $Dot = new Dot($_SESSION);

        return $Dot->has($key);
    }

    public function delete(string $key = null)
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
    public function destroy()
    {
        if ($this->cli and !$this->started()) {
            session_start();
        }
        if ($this->cli === false) {
            session_destroy();
        }
        $this->started = false;
        $_SESSION = [];
    }

    /**
     * Checks if session started
     *
     * @return void
     */
    public function started() : bool
    {
        if ($this->started or session_status() === PHP_SESSION_ACTIVE) {
            return true;
        }
        return false;
    }

    /**
     * Sets (if headers not already sent) and gets the session id
    *
    * @param string $id
    * @return string
    */
    public function id(string $id = null) : string
    {
        if ($id and !headers_sent()) {
            session_id($id);
        }
        return session_id();
    }
}
