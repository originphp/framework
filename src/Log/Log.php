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

namespace Origin\Log;

use Origin\Core\StaticConfigTrait;
use Origin\Exception\InvalidArgumentException;
use Origin\Log\Engine\BaseEngine;

class Log
{
    use StaticConfigTrait;
    /**
     * Default Configuration
     *
     * @var array
     */
    protected static $defaultConfig = [
        'default' => [
            'className' => 'Origin\Log\Engine\FileEngine'
            ]
    ];

    /**
     * Holds the default levels
     *
     * @var array
     */
    protected static $levels = [
        'emergency', 'alert', 'critical', 'error',  'warning', 'notice', 'info', 'debug'
    ];

    /**
    * Holds the loaded loggers
    *
    * @var array
    */
    protected static $loaded = null;

    /**
     * System is unusable.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function emergency(string $message, array $context = []) : void
    {
        static::write('emergency', $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function alert(string $message, array $context = []) : void
    {
        static::write('alert', $message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function critical(string $message, array $context = []) : void
    {
        static::write('critical', $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function error(string $message, array $context = []) : void
    {
        static::write('error', $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function warning(string $message, array $context = []) : void
    {
        static::write('warning', $message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function notice(string $message, array $context = []) : void
    {
        static::write('notice', $message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function info(string $message, array $context = []) : void
    {
        static::write('info', $message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function debug(string $message, array $context = []) : void
    {
        static::write('debug', $message, $context);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level ('emergency', 'alert', 'critical', 'error',  'warning', 'notice', 'info', 'debug')
     *  - emergency: system is unusable
     *  - alert: action must be taken immediately
     *  - critical: critical conditions
     *  - error: error conditions
     *  - warning: warning conditions
     *  - notice: normal, but significant, condition
     *  - info: informational message
     *  - debug: debug-level message
     * @param string $message message and you can use placeholders {key}
     * @param array $context this is an array which can contain
     *  - context: array with key value for placeholders PSR3 style
     *  - channel: name of the channel
     *  - array: any other data will be converted to a json string
     * @return void
     */
    public static function write(string $level, string $message, array $context = []) : void
    {
        $context += ['channel' => 'application'];
        $channel = $context['channel'];
        unset($context['channel']);

        if (static::$loaded === null) {
            static::loadEngines();
        }
   
        if (!in_array($level, static::$levels)) {
            throw new InvalidArgumentException(sprintf('Invalid log level `%s`.', $level));
        }

        foreach (static::$loaded as $logger) {
            $levels = $logger->levels();
            if (!empty($levels) and !in_array($level, $levels)) {
                continue;
            }
            $channels = $logger->channels();
            if (!empty($channels) and !in_array($channel, $channels)) {
                continue;
            }
            $logger->channel($channel);
            $logger->{$level}($message, $context);
        }
    }
    
    /**
     * Loads the engines for logging
     *
     * @return void
     */
    protected static function loadEngines() : void
    {
        $engines = static::config();
        foreach ($engines as $name => $config) {
            if (isset($config['engine'])) {
                $config['className'] = __NAMESPACE__  . "\Engine\\{$config['engine']}Engine";
            }
            if (empty($config['className']) or !class_exists($config['className'])) {
                throw new InvalidArgumentException("Log engine for {$name} could not be found");
            }
            
            static::$loaded[$name] = new $config['className']($config);
        }
    }

    /**
     * Gets a particular engine
     *
     * @param string $name
     * @throws \Origin\Exception\InvalidArgumentException
     * @return \Origin\Log\BaseEngine
     */
    public static function engine(string $name) : ?BaseEngine
    {
        if (static::$loaded === null) {
            static::loadEngines();
        }
        if (isset(static::$loaded[$name])) {
            return static::$loaded[$name];
        }
        throw new InvalidArgumentException("{$name} config does not exist");
    }

    /**
     * Resets all the loggers and configuration. This is really handy for testing and it will clear :
     *
     *  - All config
     *  - All loaded items
     *
     * @return void
     */
    public static function reset() : void
    {
        static::$loaded = null;
        static::$config = [];
    }
}
