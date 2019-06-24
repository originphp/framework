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

use Origin\Utility\Security;
use Origin\Core\Configure;

/**
 * Cookie Component - makes it easy to work with cookies, cookies are set using the response
 * object. By default contents of cookies are encrypted.
 */

class Cookie
{
    /**
     * Encrypted cookie values will start with this. It is base64 encoded string
     * with a . appended to it. Originally this was an encrypted string but it was long. We
     * just need an unique identifier. enc:1
     */
    const prefix =  'T3JpZ2lu==.';

    protected $key = null;

    /**
     * Constructor - Create key to be used by encryption
     */
    public function __construct()
    {
        $this->key = md5(Configure::read('Security.pepper')); // Create a 32 byte key using the pepper
    }

    /**
     * Reads a value of a cookie
     *
     * @param string $name
     * @return string|null
     */
    public function read(string $name)
    {
        if (isset($_COOKIE[$name])) {
            return $this->unpack($_COOKIE[$name]);
        }
        return null;
    }

    /**
     * Writes a cookie
     *
     *  $cookie->write('key',$value);
     *  $cookie->write('key',$value,strtotime('+1 day'));
     *
     * @param string $key
     * @param mixed $value
     * @param int $expire unix time stamp
     * @param array $options setcookie params: encrypt,path,domain,secure,httpOnly
     * @return void
     */
    public function write(string $name, $value, int $expire = 0, array $options=[])
    {
        $options += [
            'path' => '/', // path on server
            'domain' => '', // domains cookie will be available on
            'secure' => false, // only send if through https
            'httpOnly' => false, // only available to  HTTP protocol not to javascript
            'encrypt' => true
        ];
    
        extract($options);
        $value = $this->pack($value, $options['encrypt']);
        $this->setCookie($name, $value, $expire, $path, $domain, $secure, $httpOnly);
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
        $this->write($name, "", time() - 3600);
    }

    /**
     * Checks if a cookie exists
     *
     * @param string $name
     * @return void
     */
    public function exists(string $name) : bool
    {
        if (isset($_COOKIE[$name])) {
            return true;
        }
        return false;
    }
    
    /**
     * Deletes all cookies
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
    protected function setCookie($name, $value, $expire=0, $path='/', $domain='', $secure=false, $httpOnly=false)
    {
        setcookie($name, $value, $expire, $path, $domain, $secure, $httpOnly);
    }

    /**
     * Handles the packing of the data, serializing, encrypting and encoding
     *
     * @param mixed $value
     * @return string
     */
    protected function pack($value, $encrypt=true)
    {
        if (is_array($value)) {
            $value = json_encode($value);
        }
       
        if ($encrypt) {
            $value = self::prefix . Security::encrypt($value, $this->key);
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
        $length = strlen(self::prefix);
        if (substr($value, 0, $length) === self::prefix) {
            $value = substr($value, $length);
            $value = Security::decrypt($value, $this->key);
        }
        if (substr($value, 0, 1)==='{') {
            $value = json_decode($value);
        }
        return $value;
    }
}
