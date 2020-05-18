<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
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
namespace Origin\Core;

use Origin\Core\Exception\Exception;
use Origin\Core\Exception\FileNotFoundException;

class Config
{
    /**
     * Dot Object
     *
     * @var \Origin\Core\Dot
     */
    protected static $dot = null;

    /**
     * Returns the dot object
     *
     * @return \Origin\Core\Dot
     */
    protected static function dot() : Dot
    {
        if (static::$dot === null) {
            static::$dot = new Dot();
        }

        return static::$dot;
    }
 
    /**
     * Loads values from a PHP file that returns an array
     *
     * @param string $filename
     * @param string $key
     * @return void
     */
    public static function load(string $filename, string $key = null) : void
    {
        if (! is_file($filename)) {
            throw new FileNotFoundException(sprintf('%s could not be found.', $filename));
        }

        $array = include $filename;

        if (! is_array($array)) {
            throw new Exception(sprintf('Configuration file %s did not return an array', $filename));
        }

        if ($key) {
            $array = [$key => $array];
        }

        foreach ($array as $key => $value) {
            static::dot()->set($key, $value);
        }
    }

    /**
     * Writes to global config
     *
     * @param string $key The key to use, accepts also dot notation e.g. Session.timeout
     * @param mixed $value The value to set
     * @return void
     */
    public static function write(string $key = null, $value = null) : void
    {
        static::dot()->set($key, $value);
        if ($key === 'debug') {
            ini_set('display_errors', (string) $value);
        }
    }

    /**
     * Reads from the global config
     *
     * @param string $key The key to read, accepts also dot notation e.g. Session.timeout
     * @return mixed
     */
    public static function read(string $key = null)
    {
        if ($key === null) {
            return static::dot()->items();
        }

        return static::dot()->get($key);
    }

    /**
     * Checks if a key exists on the gobal config
     *
     * @param string $key The key to check, accepts also dot notation e.g. Session.timeout
     * @return bool
     */
    public static function exists(string $key = null) : bool
    {
        return static::dot()->has($key);
    }

    /**
     * Deletes a value from the gobal config
     *
     * @param string $key The key to use, accepts also dot notation e.g. Session.timeout
     * @return bool
     */
    public static function delete(string $key = null) : bool
    {
        return static::dot()->delete($key);
    }

    /**
     * Reads a variable from configuration and then deletes it
     *
     * @param string $key
     * @return mixed
     */
    public static function consume(string $key)
    {
        $value = static::read($key);
        static::delete($key);

        return $value;
    }
}
