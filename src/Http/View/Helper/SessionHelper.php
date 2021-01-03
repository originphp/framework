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
namespace Origin\Http\View\Helper;

use Origin\Http\Session;

/**
 * Session helper - makes it easy to work with sessions.
 */

class SessionHelper extends Helper
{
    /**
     * Session Object
     *
     * @var \Origin\Http\Session
     */
    protected $session = null;

    /**
     * Gets the Session object from the Request
     *
     * @return \Origin\Http\Session
     */
    protected function session(): Session
    {
        return $this->request()->session();
    }

    /**
     * Reads a value from the session
     *
     * @param string $name
     * @return mixed
     */
    public function read(string $name)
    {
        return $this->session()->read($name);
    }

    /**
     * Writes a session
     *
     *  $session->write('key',$value);
     *  $session->write('key',$value,strtotime('+1 day'));
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function write(string $name, $value): void
    {
        $this->session()->write($name, $value);
    }

    /**
     * Deletes a session
     *
     * @param string $name
     * @return void
     */
    public function delete(string $name): void
    {
        $this->session()->delete($name);
    }

    /**
     * Checks if an item exists in session
     *
     * @param string $name
     * @return bool
     */
    public function exists(string $name): bool
    {
        return $this->session()->exists($name);
    }

    /**
     * Clears the session
     *
     * @return void
     */
    public function clear(): void
    {
        $this->session()->clear();
    }
    
    /**
     * Deletes all sessions
     *
     * @return void
     */
    public function destroy(): void
    {
        $this->session()->destroy();
    }
}
