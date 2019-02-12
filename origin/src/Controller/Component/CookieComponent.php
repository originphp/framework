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

use Origin\Utils\Security;

/**
 * Cookie Component - makes it easy to work with cookies, cookies are set using the response
 * object. By default contents of cookies are encrypted.
 */

class CookieComponent extends Component
{
    public $defaultConfig = [
        'path' => '/', // path on server
        'domain' => '', // domains cookie will be available on
        'secure' => false, // only send if through https
        'httpOnly' => false, // only available to  HTTP protocol not to javascript
        'encrypt' => true, // wether to encrypt contents or not
    ];

    /**
     * Reads a value of a cookie
     *
     * @param string $name
     * @return string|null
     */
    public function read(string $name)
    {
        $cookies = $this->response->cookies();

        if (isset($cookies[$name])) {
            return $this->unpack($cookies[$name]['value']);
        }
        if (isset($_COOKIE[$name])) {
            return $this->unpack($_COOKIE[$name]);
        }
      
        return null;
    }

    /**
     * Writes a cookie
     *
     *  $this->Cookie->write('key',$value);
     *  $this->Cookie->write('key',$value,strtotime('+1 day'));
     * @param string $key
     * @param mixed $value
     * @param integer $expire unix timestamp
     * @return void
     */
    public function write(string $name, $value, int $expire=0)
    {
        $this->setCookie($name, ['value'=>$this->pack($value),'expire'=>$expire]);
    }

    /**
     * Deletes a cookie
     *
     * @param string $name
     * @return void
     */
    public function delete(string $name)
    {
        unset($_COOKIE[$name]);
        $this->setCookie($name, ['expire'=>time() - 3600]);
    }

    /**
     * Checks if a cookie exists
     *
     * @param string $name
     * @return void
     */
    public function check(string $name)
    {
        $cookies = $this->response->cookies();
        if (isset($cookies[$name]) or isset($_COOKIE[$name])) {
            return true;
        }
        return false;
    }
    
    /**
     * Delets all cookies
     *
     * @return void
     */
    public function destroy()
    {
        foreach ($_COOKIE as $name => $value) {
            $this->delete($name);
        }
    }

    /**
     * A wrapper for response cookie to make it easier to test
     *
     * @param string $name
     * @param array $value
     * @return void
     */
    protected function setCookie(string $name, array $value)
    {
        $this->response->cookie($name, $value);
    }

    /**
     * Handles the packing of the data, serializing, encrypting and encoding
     *
     * @param mixed $value
     * @return string
     */
    protected function pack($value)
    {
        $value = json_encode($value);
        if ($this->config['encrypt']) {
            $value = base64_encode(Security::encrypt($value));
        }

        return $value;
    }

    /**
     * Handles the unpacking of the data, serializing, decrypting and decoding
     *
     * @param string $value
     * @return mixed
     */
    protected function unpack(string $value)
    {
        if ($this->config['encrypt']) {
            $value = Security::decrypt(base64_decode($value));
        }
        return json_decode($value);
    }
}
