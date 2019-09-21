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
namespace Origin\Core;

use Origin\Exception\NotFoundException;

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
     * Loads values from a file e.g. config/application.php
     *
     * @param string $filename
     * @return bool
     */
    /* @todo not implemented or tested
    public static function load(string $filename){
        if(is_readable($filename)){
            $result = include $filename;
            if(is_array($result)){
                foreach($result as $key => $value){
                    static::dot()->set($key, $value);
                }
                return true;
            }
            return false;
        }
        throw new NotFoundException(sprintf('%s could not be found.', $filename));
    }
    */
   
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
        return static::dot()->get($key);
    }

    /**
     * Checks if a key exists on the gobal config
     *
     * @param string $key The key to check, accepts also dot notation e.g. Session.timeout
     * @return bool
     */
    public static function exists(string $key = null) :bool
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
}
