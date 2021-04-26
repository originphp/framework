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
namespace Origin\Http;

use Origin\Http\Session\Engine\PhpEngine;
use Origin\Http\Session\SessionEngineInterface;

/**
 * This class is nessary for backwards compatability, however this could be seat as a class redirect and use
 * the engine directly. TODO: think about this
 */
class Session
{
    /**
     * @var \Origin\Http\Session\SessionEngineInterface
     */
    private $session;

    /**
     * Optional constructor argument has been provided for backwards compatability.
     * TODO: Consider it to not be optional in 4.x, BC
     *
     * @param \Origin\Http\Session\SessionEngineInterface $session
     */
    public function __construct(SessionEngineInterface $session = null)
    {
        $this->session = $session ?: new PhpEngine();
    }
   
    /**
     * Starts the session
     *
     * @return bool
     */
    public function start(): bool
    {
        return $this->session->start();
    }

    /**
     * Checks if session started
     *
     * @return bool
     */
    public function started(): bool
    {
        return $this->session->started();
    }

    /**
     * Sets and gets the session ID. New sessions will be started with this ID if it is a valid
     * hexidemcial string with the same length as configured in Session.idLength
    * @see https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html
    * @param string $id
    * @return string|void
    */
    public function id(string $id = null)
    {
        if ($id === null) {
            return $this->session->id();
        }

        if (! $this->session->started()) {
            $this->session->id($id);
        }
    }
    
    /**
     * Sets or gets the name. New sessions will be created with this id
     *
     * @param string $name
     * @return void
     */
    public function name(string $name = null)
    {
        if ($name === null) {
            return $this->session->name();
        }
        
        if (! $this->session->started()) {
            $this->session->name($name);
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
        return $this->session->read($key, $default);
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
        $this->session->write($key, $value);
    }

    /**
     * Checks if a key exists in the session
     *
     * @param string $key
     * @return boolean
     */
    public function exists(string $key): bool
    {
        return $this->session->exists($key);
    }
    /**
     * Deletes a key in the session
     *
     * @param string $key
     * @return boolean
     */
    public function delete(string $key): bool
    {
        return $this->session->delete($key);
    }

    /**
     * Destroys the session.
     *
     * @return void
     */
    public function destroy(): void
    {
        $this->session->destroy();
    }

    /**
     * Closes the session
     *
     * @return boolean
     */
    public function close(): bool
    {
        return $this->session->close();
    }

    /**
     * Clears the session data
     *
     * @return void
     */
    public function clear(): void
    {
        $this->session->clear();
    }

    /**
     * Gets the session data as an array
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->session->toArray();
    }
}
