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

namespace Origin\Core;

/**
 * A minimalistic PSR friendly logger- which currently just works with files.
 */
/**
 * @codeCoverageIgnore
 */
class Logger
{
    /**
     * A simple descriptive name which logs are related to. Like a category
     *
     * @var string
     */
    protected $channel = null;
    
    /**
     * Filename where the log will be written too
     *
     * @var string
     */
    
    protected $filename = LOGS . DS .  'application.log';

    /**
     * Constructor function
     * @param string $channel  a simple descriptive name which logs are related to
     */
    public function __construct(string $channel)
    {
        $this->channel = $channel;
        if (Configure::read('debug')) {
            $this->filename = LOGS . DS .  'development.log';
        }
        deprecationWarning('Logger has been deprecated use Log\Log instead');
    }
    
    /**
     * Sets or gets the filename for the logger
     *
     * @param string $filename
     * @return Logger
     */
    public function filename(string $filename = null)
    {
        if ($filename === null) {
            return $this->filename;
        }
        $this->filename = $filename;
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function debug(string $message, array $context = [])
    {
        $this->log('debug', $message, $context);
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
    public function info(string $message, array $context = [])
    {
        $this->log('info', $message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function notice(string $message, array $context = [])
    {
        $this->log('notice', $message, $context);
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
    public function warning(string $message, array $context = [])
    {
        $this->log('warning', $message, $context);
    }

    /**
        * Runtime errors that do not require immediate action but should typically
        * be logged and monitored.
        *
        * @param string $message
        * @param array $context
        * @return void
        */
    public function error(string $message, array $context = [])
    {
        $this->log('error', $message, $context);
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
    public function critical(string $message, array $context = [])
    {
        $this->log('critical', $message, $context);
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
    public function alert(string $message, array $context = [])
    {
        $this->log('alert', $message, $context);
    }

    /**
      * System is unusable.
      *
      * @param string $message
      * @param array $context
      * @return void
      */
    public function emergency(string $message, array $context = [])
    {
        $this->log('emergency', $message, $context);
    }

    /**
      * Workhorse for the logging methods
      *
      * @param string $level e.g debug, info, notice, warning, error, critical, alert, emergency.
      * @param string $message 'this is a {what}'
      * @param array $context  ['what'='string']
      * @return void
      */
    protected function log(string $level, string $message, array $context = [])
    {
        $message = $this->interpolate($message, $context);
        $data = '['.date('Y-m-d G:i:s') . '] ' . $this->channel . ' ' . strtoupper($level). ': ' .  $message . "\n";

        return file_put_contents($this->filename, $data, FILE_APPEND | LOCK_EX);
    }

    /**
    * Intropolates context values into the message placeholders.
    *
    * @param string $message
    * @param array $context
    * @return string
    */
 
    protected function interpolate(string $message, array $context) : string
    {
        $replace = [];
        foreach ($context as $key => $value) {
            if (! is_array($value) and (! is_object($value) or method_exists($value, '__toString'))) {
                $replace['{'. $key . '}'] = $value;
            }
        }

        return strtr($message, $replace);
    }
}
