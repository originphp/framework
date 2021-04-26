<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2021 Jamiel Sharief.
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
namespace Origin\Http\Session\Engine;

use Origin\Core\Dot;
use RuntimeException;
use Origin\Core\Exception\Exception;

/**
 * Native PHP sessions
 *
 * TODO: refactor no longer need to be CLI friendly due to ArraySession
 */
class PhpEngine extends BaseEngine
{
    protected function initialize(): void
    {

        /**
        * Security considerations:
        *
        * - session.cookie_secure: If the connection is HTTPS then only send cookies through this.
        * - session.cookie_httponly: Tell the browsers that the session cookies should not availble client side
        *   to help prevent cookie theft (a majority of XSS attacks include highjacking the session cookie).
        */

        if (! $this->started() && ! headers_sent()) {
            $this->setIniConfig([
                'session.name' => $this->name,
                'session.save_path' => TMP . DS . 'sessions',
                'session.gc_maxlifetime' => $this->timeout,
                'session.cookie_secure' => env('HTTPS') ? 1 : 0,
                'session.cookie_httponly' => 1
            ]);
        }

        session_register_shutdown();
    }

    /**
     * Configs the session for use
     *
     * @return void
     */
    protected function setIniConfig(array $config): void
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
    public function start(): bool
    {
        if ($this->started) {
            return false;
        }

        if (isConsole()) {
            $_SESSION = [];

            return $this->started = true;
        }

        if ($this->started()) {
            throw new Exception('Session already started.');
        }
      
        $this->startSession();
        
        return $this->started = true;
    }
    
    /**
     * Main logic for starting session
     *
     * @return void
     */
    protected function startSession(): void
    {
        if ($this->id === null || ! $this->isValid($this->id)) {
            $this->id = $this->generateId();
        }

        session_name($this->name);
        session_id($this->id);
        
        if (! session_start()) {
            throw new Exception('Error starting a session.');
        }

        if ($this->timedOut()) {
            $this->destroy();
            $this->start();
        }
    }

    /**
     * Writes a value into the session, accepts dot notation
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function write(string $key, $value): void
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
    protected function overwrite(array $data): void
    {
        foreach ($_SESSION as $key => $value) {
            if (! isset($data[$key])) {
                unset($_SESSION[$key]);
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
    public function read(string $key, $default = null)
    {
        return (new Dot($_SESSION))->get($key, $default);
    }

    /**
     * Checks if a key exists in the session
     *
     * @param string $key
     * @return boolean
     */
    public function exists(string $key): bool
    {
        return (new Dot($_SESSION))->has($key);
    }
    /**
     * Deletes a key in the session
     *
     * @param string $key
     * @return boolean
     */
    public function delete(string $key): bool
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
     * Closes the session
     *
     * @return boolean
     */
    public function close(): bool
    {
        if ($this->started()) {
            return false;
        }

        if (isConsole()) {
            $_SESSION = [];
        } elseif (! session_write_close()) {
            throw new RuntimeException('Error closing session');
        }

        $this->started = false;

        return true;
    }

    /**
     * Destroys the session.
     *
     * @return void
     */
    public function destroy(): void
    {
        if (! $this->started()) {
            $this->start();
        }
        if (! headers_sent()) {
            // @codeCoverageIgnoreStart
            session_destroy();
            // @codeCoverageIgnoreEnd
        }
        $this->id = null;
        $this->started = false;
        $_SESSION = [];
    }

    /**
     * Checks if session started
     *
     * @return bool
     */
    public function started(): bool
    {
        return $this->started || session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * Clears the session data
     *
     * @return void
     */
    public function clear(): void
    {
        $_SESSION = [];
    }

    /**
     * Undocumented function
     *
     * @return array
     */
    public function toArray(): array
    {
        return empty($_SESSION) ? [] : $_SESSION;
    }
}
