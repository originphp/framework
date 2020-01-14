<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
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
namespace Origin\Http;

use Origin\Core\Dot;
use Origin\Core\Config;
use Origin\Security\Security;
use Origin\Core\Exception\Exception;

class Session
{
    /**
     * Bool when started
     *
     * @var boolean
     */
    protected $started = false;

    /**
     * @var integer
     */
    protected $timeout = 3600;

    /**
     * Constructor
     */
    public function __construct()
    {
        /**
        * Security considerations:
        *
        * - session.cookie_secure: If the connection is HTTPS then only send cookies through this.
        * - session.cookie_httponly: Tell the browsers that the session cookies should not availble client side
        *   to help prevent cookie theft (a majority of XSS attacks include highjacking the session cookie).
        */
    
        $config = [
            'session.save_path' => TMP . DS . 'sessions',
            'session.cookie_httponly' => 1,
        ];

        if (env('HTTPS')) {
            $config['session.cookie_secure'] = 1;
        }

        if (! $this->started() and ! headers_sent()) {
            $this->setIniConfig($config);
        }

        // For DOT and CLI
        if (! isset($_SESSION)) {
            $_SESSION = [];
        }

        if ($this->started() === false) {
            $this->start();
        }
    }

    /**
     * Configs the session for use
     *
     * @return void
     */
    protected function setIniConfig(array $config) : void
    {
        foreach ($config as $option => $value) {
            if (ini_set($option, (string) $value) === false) {
                throw new Exception(sprintf('Error configuring session for `%s`', $option));
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

        if (isConsole()) {
            return $this->started = true;
        }

        if ($this->started()) {
            throw new Exception('Session already started.');
        }

        /**
         * Validate cookie and create secure session ID
         * @see https://www.owasp.org/index.php/Full_Path_Disclosure
         */
        $id = $this->validateCookie();
      
        $this->startSession($id);
        
        return $this->started = true;
    }
    
    /**
     * Main logic for starting session
     *
     * @param string $id
     * @return void
     */
    protected function startSession(string $id = null) : void
    {
        if ($id === null) {
            $this->id(Security::uuid());
        }
        
        if (! session_start()) {
            throw new Exception('Error starting a session.');
        }

        if ($this->timedOut($this->timeout)) {
            $this->destroy();
            $this->start();
        }
    }

    /**
     * This will validate the cookie and return the ID if it is correct
     *
     * @return string|null
     */
    protected function validateCookie() : ?string
    {
        $name = session_name();

        $id = null;
        if (isset($_COOKIE[$name])) {
            $id = $_COOKIE[$name];
        }
        if ($id and ! preg_match('/\b[0-9a-f]{8}\b-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-\b[0-9a-f]{12}\b/', $id)) {
            $this->destroy();

            return null;
        }

        return $id;
    }

    /**
     * Checks if session timedout
     *
     * @param integer $timeout
     * @return boolean
     */
    protected function timedOut($timeout = 3600) : bool
    {
        if (Config::exists('Session.timeout')) {
            $timeout = Config::read('Session.timeout');
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
     * @return void
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
        $this->overwrite($Dot->items());
    }

    /**
     * Overwrite each session var, for PHP reasons
     *
     * @param array $data
     * @return void
     */
    protected function overwrite(array $data) : void
    {
        foreach ($_SESSION as $key => $value) {
            if (! isset($data[$key])) {
                unset($_SESSION[$key]);
                continue;
            }
        }
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
            $this->overwrite($Dot->items());

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
        if (! $this->started()) {
            // @codeCoverageIgnoreStart
            session_start();
            // @codeCoverageIgnoreEnd
        }
        if (isConsole() === false) {
            // @codeCoverageIgnoreStart
            session_destroy();
            // @codeCoverageIgnoreEnd
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
    * @return string|void
    */
    public function id(string $id = null)
    {
        if ($id === null) {
            return session_id();
        }

        if (! headers_sent()) {
            // @codeCoverageIgnoreStart
            session_id($id);
            // @codeCoverageIgnoreEnd
        }
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
}
