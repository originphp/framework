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

namespace Origin\Log\Engine;

use Origin\Core\ConfigTrait;
use Origin\Core\HookTrait;

abstract class BaseEngine
{
    use ConfigTrait, HookTrait;

    /**
     * default config used by ConfigTrait
     *
     * @var array
     */
    protected $defaultConfig = [];

    /**
     * Holds the channel
     *
     * @var string
     */
    protected $channel = 'application';

    /**
     * Constructor
     *
     * @param array $config  duration,prefix,path
     */
    public function __construct(array $config = [])
    {
        $this->config($config);
        $this->executeHook('initialize', [$config]);
    }

    /**
     * Sets or gets the channel
     *
     * @param string $channel
     * @return string
     */
    public function channel(string $channel = null) : string
    {
        if ($channel === null) {
            return $this->channel;
        }

        return $this->channel = $channel;
    }

    /**
     * Gets the levels on this logger
     *
     * @return array
     */
    public function levels() : array
    {
        $levels = $this->config('levels');
        if (is_array($levels) and ! empty($levels)) {
            return $levels;
        }

        return [];
    }

    /**
    * Gets the channels on this logger
    *
    * @return array
    */
    public function channels() : array
    {
        $channels = $this->config('channels');
        if (is_array($channels) and ! empty($channels)) {
            return $channels;
        }

        return [];
    }

    /**
    * System is unusable.
    *
    * @param string $message
    * @param array $context
    * @return void
    */
    public function emergency(string $message, array $context = []) : void
    {
        $this->log('emergency', $message, $context);
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
    public function alert(string $message, array $context = []) : void
    {
        $this->log('alert', $message, $context);
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
    public function critical(string $message, array $context = []) : void
    {
        $this->log('critical', $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function error(string $message, array $context = []) : void
    {
        $this->log('error', $message, $context);
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
    public function warning(string $message, array $context = []) : void
    {
        $this->log('warning', $message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function notice(string $message, array $context = []) : void
    {
        $this->log('notice', $message, $context);
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
    public function info(string $message, array $context = []) : void
    {
        $this->log('info', $message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function debug(string $message, array $context = []) : void
    {
        $this->log('debug', $message, $context);
    }

    /**
    * Logs using a level
    *
    * @param string $level
    * @param string $message
    * @param array $context
    * @return void
    */
    abstract public function log(string $level, string $message, array $context = []) : void;

    /**
     * Interpolates context values into the message placeholders.
     *
     * @internal keep this for custom
     *
     * @param string $message
     * @param array $context
     * @return string
     */
    protected function interpolate(string $message, array $context): string
    {
        $replace = [];
        foreach ($context as $key => $value) {
            if (! is_array($value) and (! is_object($value) or method_exists($value, '__toString'))) {
                $replace['{' . $key . '}'] = $value;
            }
        }

        return strtr($message, $replace);
    }

    /**
     * Formats a log message
     *
     * @param string $level
     * @param string $message
     * @param array $context
     * @return string
     */
    protected function format(string $level, string $message, array $context) : string
    {
        
        // Get Values to replace
        $replace = [];
        foreach ($context as $key => $value) {
            if (strpos($message, '{' . $key .'}') !== false) {
                $replace[$key] = $value;
                unset($context[$key]);
            }
        }

        $message = $this->interpolate($message, $replace);

        // Encode remaining data
        if ($context) {
            $message .= ' ' . json_encode($context);
        }

        return sprintf('[%s] %s %s: %s', date('Y-m-d G:i:s'), $this->channel(), strtoupper($level), $message);
    }
}
