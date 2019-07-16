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

namespace Origin\Http;

use Origin\Core\Dot;
use Origin\Exception\Exception;
use Origin\Core\Configure;

class Session
{
    protected $started = false;

    public function __construct()
    {
        $this->configureSession();

        if ($this->started() === false) {
            $this->start();
        }
        // For DOT
        if (!isset($_SESSION)) {
            $_SESSION = [];
        }
    }

    /**
     * Configures the session for use
     *
     * @return void
     */
    protected function configureSession() : void
    {
        if ($this->started() || headers_sent()) {
            return;
        }
        $config = [];
        $config['session.save_path'] = TMP . DS . 'sessions';
       
        /**
         * If the connection is HTTPS then only send cookies
         * through this.
         */
        if (env('HTTPS')) {
            $config['session.cookie_secure'] = 1;
        }
        /**
         * Tell the browsers that the session cookies should not availble client side
         * to help prevent cookie theft (a majority of XSS attacks include highjacking the
         * session cookie).
         */
        $config['session.cookie_httponly'] = 1;

        foreach ($config as $option => $value) {
            if (ini_set($option, $value) === false) {
                throw new Exception(sprintf('Error configuring session for `%s', $options));
            }
        }
    }

    /**
     * Starts the session
     *
     * @return bool
     */
    public function start() : bool
    {
        if ($this->started) {
            return false;
        }

        if (PHP_SAPI === 'cli') {
            return $this->started = true;
        }

        if ($this->started() === PHP_SESSION_ACTIVE) {
            throw new Exception('Session already started.');
        }

        /**
         * Full Path Disclosure attack
         * @see https://www.owasp.org/index.php/Full_Path_Disclosure
         */
        $name = session_name();
        $id = null;
        if (isset($_COOKIE[$name])) {
            $id = $_COOKIE[$name];
        }
        if ($id and !preg_match('/\b[0-9a-f]{8}\b-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-\b[0-9a-f]{12}\b/', $id)) {
            $this->destroy();
            $id = null;
        }

        if ($id === null) {
            session_id(uuid());
        }
      
        if (!session_start()) {
            throw new Exception('Error starting a session.');
        }
        
        $this->started = true;

        if ($this->timedOut()) {
            $this->destroy();
            $this->start();
        }
        return true;
    }

    /**
     * Checks if session timedout
     *
     * @param integer $timeout
     * @return boolean
     */
    protected function timedOut($timeout = 3600) : bool
    {
        if (Configure::exists('Session.timeout')) {
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

    /**
     * Writes a value into the session, accepts dot notation
     *
     * @param string $key
     * @param mixed $value
     * @return void;
     */
    public function write(string $key = null, $value = null) : void
    {
        $Dot = new Dot($_SESSION);
        $Dot->set($key, $value);
        if (strpos($key, '.') === false) {
            $_SESSION[$key] = $value;
            return;
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
     * @param string $key
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

    /**
     * Checks if a key exists in the session
     * @codeCoverageIgnore
     * @param string $key
     * @return boolean
     */
    public function check(string $key = null) : bool
    {
        deprecationWarning('Session::check is depreciated use session:exists');
        return $this->exists($key);
    }

    /**
     * Checks if a key exists in the session
     *
     * @param string $key
     * @return boolean
     */
    public function exists(string $key = null) : bool
    {
        $Dot = new Dot($_SESSION);
        return $Dot->has($key);
    }
    /**
     * Deletes a key in the session
     *
     * @param string $key
     * @return boolean
     */
    public function delete(string $key = null) :bool
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
     *
     * @return void
     */
    public function destroy() : void
    {
        if (!$this->started()) {
            session_start();
        }
        if (PHP_SAPI !== 'cli') {
            session_destroy();
        }
        $this->started = false;
        $_SESSION = [];
    }

    /**
     * Checks if session started
     *
     * @return bool
     */
    public function started() : bool
    {
        return ($this->started or session_status() === PHP_SESSION_ACTIVE);
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

    /**
     * Clears the session data
     *
     * @return void
     */
    public function clear() : void
    {
        $_SESSION = [];
    }
    
    /**
     * Resets the session data
     *
     * @return void
     */
    public function reset() : void
    {
        $this->clear();
        $_SESSION = null;
        if (!headers_sent()) {
            session_write_close();
            $this->start();
        }
    }
}
