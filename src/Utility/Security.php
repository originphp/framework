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
declare(strict_types=1);
namespace Origin\Utility;

use Origin\Core\Config;
use Origin\Exception\Exception;
use Origin\Exception\InvalidArgumentException;

class Security
{
    const CIPHER = 'AES-256-CBC';

    /**
     * Hashes a string. This is not for passwords.
     *
     * @param string $string
     * @param string $string
     * @param array $options options keys are
     * - pepper: (default:false). Set to true to use Security.pepper or set a string to use.
     * - type: (default:sha256)
     * @return string
     */
    public static function hash(string $string, array $options = []): string
    {
        $options += ['pepper' => false, 'type' => 'sha256'];
        $algorithm = strtolower($options['type']);

        if ($options['pepper'] === true) {
            $options['pepper'] = Config::read('Security.pepper');
        }
        if ($options['pepper']) {
            $string = $options['pepper'] . $string;
        }

        if (! in_array($algorithm, hash_algos())) {
            throw new Exception('Invalid hashing algorithm');
        }

        return hash($algorithm, $string);
    }

    /**
     * Hashes a password using the current best practice which is Bcrypt
     *
     * @param string $password
     * @return string
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verifies a password against a hash created with hashPassword
     *
     * @param string $password
     * @param string $hash
     * @return boolean
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Compares two strings are equal in a safe way.
     * @see https://blog.ircmaxell.com/2014/11/its-all-about-time.html
     * @param string $original
     * @param string $compare
     * @return bool
     */
    public static function compare(string $original = null, string $compare = null): bool
    {
        if (! is_string($original) or ! is_string($compare)) {
            return false;
        }

        return hash_equals($original, $compare);
    }

    /**
     * Generates a secure 256 bits (32 bytes) key
     * @internal the size needs to be the same length as the cipher, the rest will be truncated
     * @return string
     */
    public static function generateKey(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Encrypts a string using your key. The key should be secure use. generateKey
     *
     * @see http://php.net/manual/en/function.openssl-encrypt.php
     * @param string $string
     * @param string $key must must be 256 bits (32 bytes)
     * @return string
     */
    public static function encrypt(string $string, string $key): string
    {
        if (mb_strlen($key) !== 32) {
            throw new InvalidArgumentException('Invalid Key. Key must be 256 bits (32 bytes)');
        }
        $length = openssl_cipher_iv_length(self::CIPHER);
        $iv = random_bytes($length);
        $raw = openssl_encrypt($string, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $raw, $key, true);

        return base64_encode($iv . $hmac . $raw);
    }

    /**
     * Decrypts an encrypted string
     *
     * @param string $string
     * @param string $key must must be 256 bits (32 bytes)
     * @return string|bool encrypted string
     */
    public static function decrypt(string $string, string $key)
    {
        if (mb_strlen($key) !== 32) {
            throw new InvalidArgumentException('Invalid Key. Key must be 256 bits (32 bytes)');
        }
        $string = base64_decode($string);
        $length = openssl_cipher_iv_length(self::CIPHER);
        $iv = substr($string, 0, $length);
        $hmac = substr($string, $length, 32);
        $raw = substr($string, $length + 32);
        $expected = hash_hmac('sha256', $raw, $key, true);
        if (! static::compare($expected, $hmac)) {
            return false;
        }

        return openssl_decrypt($raw, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);
    }

    /**
     * Generates a secure UID
     * @param integer $length
     * @return string
     */
    public static function uid(int $length = 16) : string
    {
        $random = random_bytes((int) ceil($length / 2));

        return substr(bin2hex($random), 0, $length);
    }

    /**
     * Generates a v4 random UUID (Universally Unique IDentifier).
     * @return string
     */
    public static function uuid() : string
    {
        return implode('-', [
            bin2hex(random_bytes(4)),
            bin2hex(random_bytes(2)),
            bin2hex(chr((ord(random_bytes(1)) & 0x0F) | 0x40)) . bin2hex(random_bytes(1)),
            bin2hex(chr((ord(random_bytes(1)) & 0x3F) | 0x80)) . bin2hex(random_bytes(1)),
            bin2hex(random_bytes(6)),
        ]);
    }
}
