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
     * Encrypts a string using openssl encrypt
     *
     * If a key is not provided, then the Security.salt will be used.  Changing the salt would
     * be like providing a different key and you will not be able to decrypt data. The best way
     * to generate a key would be to use openssl_random_pseudo_bytes.
     * All keys are hashed to 32bytes. A new initialization vector 16 bytes for AES-256-CBC is securely
     * created using random bytes for each string that is encrypted. The iv is then added to the string so
     * during decryption it can be obtained easily.
     * To store the encrypted string in the database you would have make sure type is binary or
     * base64 encode etc.
     * @see http://php.net/manual/en/function.openssl-encrypt.php
     * @param string $string
     * @param string $key
     * @return binary
     */
    public static function encrypt(string $string, string $key=null)
    {
        if (is_null($key)) {
            $key = Configure::read('Security.salt');
        }
        $key = hash('sha256', $key);
        
        $length = openssl_cipher_iv_length('AES-256-CBC');
        $initializationVector  = openssl_random_pseudo_bytes($length);
        
        return $initializationVector . openssl_encrypt($string, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $initializationVector);
    }

    /**
    * Decrypts a Security::encrypt encrypted string

    * The first 16 bytes are to be the initialization vector and the rest of the string to be the encrypted
    * data. See openssl_cipher_iv_length('AES-256-CBC') for initialization vector length
    *
    * @param string $string
    * @param string $key
    * @return string encrypted string
    */
    public static function decrypt(string $string, string $key=null)
    {
        if (is_null($key)) {
            $key = Configure::read('Security.salt');
        }
        $key = hash('sha256', $key);

        $length = openssl_cipher_iv_length('AES-256-CBC');
        $initializationVector = mb_substr($string, 0, $length, '8bit');
        $encryptedString = mb_substr($string, $length, null, '8bit');
        
        return openssl_decrypt($encryptedString, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $initializationVector);
    }
}
