<?php
declare(strict_types = 1);
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

namespace Origin\Http\Controller\Component;

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
     * Reads a value of a cookie from request
     *
     * @param string $name
     * @return string|array|null
     */
    public function read(string $name)
    {
        return $this->request()->cookies($name);
    }

    /**
     * Writes a cookie through response
     *
     *  $cookie->write('key',$value);
     *  $cookie->write('key',$value,'+1 month');
     *
     * @param string $name cookie name
     * @param mixed $value
     * @param array $options The options keys are:
     *   - expires: default:'+1 month'. a strtotime string e.g. +5 days, 2019-01-01 10:23:55
     *   - encrypt: default:true. encrypt value
     *   - path: default:'/' . Path on server
     *   - domain: domains cookie will be available on
     *   - secure: default:false. only send if through https
     *   - httpOnly: default:false. only available to HTTP protocol not to javascript
     * @return void
     */
    public function write(string $name, $value, array $options = []) : void
    {
        $this->response()->cookie($name, $value, $options);
    }

    /**
     * Deletes a cookie
     *
     * @param string $name
     * @return void
     */
    public function delete(string $name) : void
    {
        $this->response()->cookie($name, '', ['expires'=>'-60 minutes']);
    }

    /**
    * Checks if a cookie exists
    *
    * @param string $name
    * @return bool
    */
    public function exists(string $name) : bool
    {
        $cookies = $this->request()->cookies();

        return isset($cookies[$name]);
    }

    /**
     * Deletes all cookies
     *
     * @return void
     */
    public function destroy() : void
    {
        unset($_COOKIE);
        $_COOKIE = [];
    }
}
