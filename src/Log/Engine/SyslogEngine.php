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

class SyslogEngine extends BaseEngine
{
    protected $opened = false;

    /**
     * Maps to levels
     * @see https://www.php.net/manual/en/function.syslog.php
     * @var array
     */
    protected $levelMap = [
        'emergency' => LOG_EMERG,
        'alert' => LOG_ALERT,
        'critical' => LOG_CRIT,
        'error' => LOG_ERR,
        'warning' => LOG_WARNING,
        'notice' => LOG_NOTICE,
        'info' => LOG_INFO,
        'debug' => LOG_DEBUG,
    ];

    /**
     * Default configuration
     *
     * @var array
     */
    protected $defaultConfig = [
        'levels' => [],
        'channels' => [],
        // syslog settings  see https://www.php.net/manual/en/function.openlog.php
        'identity' => '',
        'option' => LOG_ODELAY,
        'facility' => LOG_USER,
    ];

    /**
      * Workhorse for the logging methods
      *
      * @param string $level e.g debug, info, notice, warning, error, critical, alert, emergency.
      * @param string $message 'this is a {what}'
      * @param array $context  ['what'='string']
      * @return bool
      */
    public function log(string $level, string $message, array $context = []) : bool
    {
        if ($this->opened === false) {
            $this->openlog($this->config('identity'), $this->config('option'), $this->config('facility'));
        }
        
        $message = $this->format($level, $message, $context);
        $priority = LOG_DEBUG;
        if (isset($this->levelMap[$level])) {
            $priority = $this->levelMap[$level];
        }

        return $this->write($priority, $message);
    }

    /**
     * Opens the syslog
     *
     * @param string $identity
     * @param int $option
     * @param int $facility
     * @return bool
     */
    protected function openlog(string $identity, int $option = null, int $facility = null) :bool
    {
        return $this->opened = openlog($identity, $option, $facility);
    }

    /**
     * Writes to the syslog
     *
     * @param integer $priority
     * @param string $message
     * @return boolean
     */
    protected function write(int $priority, string $message) :bool
    {
        return syslog($priority, $message);
    }

    /**
     * Close syslog
     */
    public function __destruct()
    {
        closelog();
    }
}
