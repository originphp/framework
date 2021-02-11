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

use Origin\Core\Config;
use Origin\Security\Security;

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
    const prefix = 'T3JpZ2lu==.';

    /**
     * Reads a value of a cookie
     *
     * @param string $name
     * @return string|array|null
     */
    public function read(string $name)
    {
        return isset($_COOKIE[$name]) ? $this->unpack($_COOKIE[$name]) : null;
    }

    /**
     * Writes a cookie
     *
     *  $cookie->write('key',$value);
     *  $cookie->write('key',$value,strtotime('+1 day'));
     *
     * @param string $name
     * @param mixed $value
     * @param array $options The options keys are:
     *   - expires: default:0. a strtotime string e.g. +5 days, 2019-01-01 10:23:55
     *   - encrypt: default:true. encrypt value
     *   - path: default:'/' . Path on server
     *   - domain: domains cookie will be available on
     *   - secure: default:false. only send if through https
     *   - httpOnly: default:false. only available to HTTP protocol not to javascript
     * @return void
     */
    public function write(string $name, $value, array $options = []): void
    {
        $options += [
            'expires' => 0,
            'path' => '/', // path on server
            'domain' => '', // domains cookie will be available on
            'secure' => false, // only send if through https
            'httpOnly' => false, // only available to  HTTP protocol not to javascript
            'encrypt' => true,
        ];
    
        extract($options);
        $value = $this->pack($value, $options['encrypt']);
        $this->setCookie($name, $value, $options['expires'], $path, $domain, $secure, $httpOnly);
    }

    /**
     * Deletes a cookie
     *
     * @param string $name
     * @return void
     */
    public function delete(string $name): void
    {
        unset($_COOKIE[$name]);
        $this->write($name, '', ['expires' => time() - 3600]);
    }

    /**
     * Checks if a cookie exists
     *
     * @param string $name
     * @return bool
     */
    public function exists(string $name): bool
    {
        return isset($_COOKIE[$name]);
    }
    
    /**
     * Deletes all cookies
     *
     * @return void
     */
    public function destroy(): void
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
     * @codeCoverageIgnore
     */
    protected function setCookie($name, $value, $expire = 0, $path = '/', $domain = '', $secure = false, $httpOnly = false): void
    {
        setcookie($name, $value, $expire, $path, $domain, $secure, $httpOnly);
    }

    /**
     * Handles the packing of the data, serializing, encrypting and encoding
     *
     * @param mixed $value
     * @return string
     */
    protected function pack($value, bool $encrypt = true): string
    {
        if (is_array($value)) {
            $value = json_encode($value);
        }

        $key = $this->securityKey();
       
        if ($encrypt && $key) {
            $value = self::prefix . Security::encrypt($value, $key);
        }
       
        return (string) $value;
    }

    /**
     * Handles the unpacking of the data, serializing, decrypting and decoding
     *
     * @param string $value
     * @return string|array|null
     */
    protected function unpack(string $value)
    {
        $length = strlen(self::prefix);
  
        $key = $this->securityKey();
        if ($key && substr($value, 0, $length) === self::prefix) {
            $value = substr($value, $length);
            $value = Security::decrypt($value, $key);
        }
        if ($value && substr($value, 0, 1) === '{') {
            $value = json_decode($value, true);
        }

        return $value;
    }

    /**
     * Gets the security key
     * @return string|null
     */
    private function securityKey(): ?string
    {
        return Config::read('App.securityKey');
    }
}
