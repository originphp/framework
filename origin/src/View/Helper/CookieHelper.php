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

use Origin\Core\Cookie;

/**
 * Cookie Helper - makes it easy to work with cookies, cookies are set using the response
 * object. By default contents of cookies are encrypted.
 */

class CookieHelper extends Helper
{
    /**
     * Cookie Object
     *
     * @var \Origin\Core\Cookie
     */
    protected $cookie = null;

    /**
     * Lazy loads the cookie object
     *
     * @return \Origin\Core\Cookie
     */
    protected function cookie()
    {
        if ($this->cookie === null) {
            $this->cookie = new Cookie();
        }
        return $this->cookie;
    }
    /**
     * Reads a value of a cookie
     *
     * @param string $name
     * @return string|null
     */
    public function read(string $name)
    {
        return $this->cookie()->read($name);
    }

    /**
     * Writes a cookie
     *
     *  $cookie->write('key',$value);
     *  $cookie->write('key',$value,strtotime('+1 day'));
     *
     * @param string $key
     * @param mixed $value
     * @param integer $expire unix timestamp
     * @return void
     */
    public function write(string $name, $value)
    {
        $this->cookie()->write($name, $value);
    }

    /**
     * Deletes a cookie
     *
     * @param string $name
     * @return void
     */
    public function delete(string $name)
    {
        $this->cookie()->delete($name);
    }

    /**
     * Checks if a cookie exists
     *
     * @param string $name
     * @return void
     */
    public function check(string $name) : bool
    {
        return $this->cookie()->check($name);
    }
    
    /**
     * Deletes all cookies
     *
     * @return void
     */
    public function destroy()
    {
        $this->cookie()->destroy();
    }
}
