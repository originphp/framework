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

namespace Origin\View\Helper;

use Origin\Core\Session;

/**
 * Session helper - makes it easy to work with sessions.
 */

class SessionHelper extends Helper
{
    /**
     * Session Object
     *
     * @var \Origin\Core\Session
     */
    protected $session = null;

    /**
     * Lazy loads the session object
     *
     * @return \Origin\Core\Session
     */
    protected function session()
    {
        if ($this->session === null) {
            $this->session = new Session();
        }
        return $this->session;
    }
    /**
     * Reads a value of a session
     *
     * @param string $name
     * @return string|null
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
     * @param string $key
     * @param mixed $value
     * @param integer $expire unix timestamp
     * @return void
     */
    public function write(string $name, $value)
    {
        $this->session()->write($name, $value);
    }

    /**
     * Deletes a session
     *
     * @param string $name
     * @return void
     */
    public function delete(string $name)
    {
        $this->session()->delete($name);
    }

    /**
     * Checks if a session exists
     *
     * @param string $name
     * @return void
     */
    public function check(string $name) : bool
    {
        return $this->session()->check($name);
    }
    
    /**
     * Deletes all sessions
     *
     * @return void
     */
    public function destroy()
    {
        $this->session()->destroy();
    }
}
