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

namespace Origin\Utility;

use Origin\Core\Configure;
use Origin\Exception\Exception;
use Origin\Exception\InvalidArgumentException;

class Security
{
    /**
    * Hashes a string
    *
    * @param string $string
    * @param string $type sha1,sha256,sha512 etc see hash_algos()
    * @param boolean|string $salt
    * @return boolean
    */
    public static function hash(string $string, string $algorithm ='sha1', $salt = false)
    {
        $algorithm = strtolower($algorithm);

        if ($salt === true) {
            $salt = Configure::read('Security.salt');
        }
        if ($salt) {
            $string = $salt . $string;
        }
        if (!in_array($algorithm, hash_algos())) {
            throw new Exception('Invalid hashing algorithm');
        }
        return hash($algorithm, $string);
    }

    /**
     * Compares two strings are equal in a safe way.
     * @see https://blog.ircmaxell.com/2014/11/its-all-about-time.html
     * @param string $original
     * @param string $compare
     * @return bool
     */
    public static function compare(string $original = null, string $compare = null) : bool
    {
        if (!is_string($original) or !is_string($compare)) {
            return false;
        }
        return hash_equals($original, $compare);
    }

    /**
     * Generates a secure 256 bits (32 bytes) key
     *
     * @return string
     */
    public static function generateKey() : string
    {
        return bin2hex(openssl_random_pseudo_bytes(16));
    }

    /**
     * Encrypts a string using your key. The key should be secure use. generateKey
     *
     * @see http://php.net/manual/en/function.openssl-encrypt.php
     * @param string $string
     * @param string $key must must be at least 256 bits (32 bytes)
     * @return string
     */
    public static function encrypt(string $string, string $key)
    {
        if (strlen($key) < 32) {
            throw new InvalidArgumentException('Invalid Key. Key must be at least 256 bits (32 bytes)');
        }
        $length = openssl_cipher_iv_length('AES-128-CBC');
        $iv = openssl_random_pseudo_bytes($length);
        $raw = openssl_encrypt($string, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $raw, $key, true);
        return base64_encode($iv . $hmac . $raw);
    }

    /**
    * Decrypts an encrypted string
    *
    * @param string $string
    * @param string $key must must be at least 256 bits (32 bytes)
    * @return string encrypted string
    */
    public static function decrypt(string $string, string $key=null)
    {
        if (strlen($key) < 32) {
            throw new InvalidArgumentException('Invalid Key. Key must be at least 256 bits (32 bytes)');
        }
        $string = base64_decode($string);
        $length = openssl_cipher_iv_length('AES-128-CBC');
        $iv = substr($string, 0, $length);
        $hmac = substr($string, $length, 32);
        $raw = substr($string, $length + 32);
        $expected = hash_hmac('sha256', $raw, $key, true);
        if (!static::compare($expected, $hmac)) {
            return false;
        }
        return openssl_decrypt($raw, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
    }
}
