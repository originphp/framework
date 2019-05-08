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

namespace Origin\Controller\Component;

use Origin\Http\Cookie;

/**
 * Cookie Component - for this and session code is being repeated, but this makes
 * it easier to learn for new users, testing becomes simpler because the component/helper
 * is already there dont have to look at the request the response. I appreciate this is not part
 * of DRY, but going to make an exception instead of hacking things. This will also give flexability
 * later for using different storage engines etc, I think.
 *
 * You can use the helper
 * $this->Cookie->doSomething
 *
 * Or you can read of the request and write on the response.
 */

class CookieComponent extends Component
{
    /**
     * Cookie Object
     *
     * @var \Origin\Http\Cookie
     */
    protected $cookie = null;

    /**
     * Lazy loads the cookie object
     *
     * @return \Origin\Http\Cookie
     */
    protected function cookie()
    {
        if ($this->cookie === null) {
            $this->cookie = new Cookie();
        }
        return $this->cookie;
    }
    /**
     * Reads a value of a cookie from request
     *
     * @param string $name
     * @return string|null
     */
    public function read(string $name)
    {
        return $this->cookie()->read($name);
    }

    /**
     * Writes a cookie through response
     *
     *  $cookie->write('key',$value);
     *  $cookie->write('key',$value,strtotime('+1 day'));
     *
     * @param string $key
     * @param mixed $value
     * @param integer $expire unix timestamp
     * @return void
     */
    public function write(string $name, $value, int $expire=0, $options=[])
    {
        $this->response()->cookie($name, $value, $expire, $options);
    }

    /**
     * Deletes a cookie
     *
     * @param string $name
     * @return void
     */
    public function delete(string $name)
    {
        $this->response()->cookie($name, "", time() - 3600);
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
        unset($_COOKIE);
        $_COOKIE=[];
    }
}
