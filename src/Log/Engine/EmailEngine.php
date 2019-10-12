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

use Origin\Mailer\Email;
use Origin\Email\Email as SmtpEmail;
use Origin\Exception\InvalidArgumentException;

class EmailEngine extends BaseEngine
{
    /**
     * Holds the last email sent
     *
     * @var \Origin\Email\Message;
     */
    protected $lastEmail = null;

    /**
     * Default configuration
     *
     * @var array
     */
    protected $defaultConfig = [
        'to' => null, // email address string/or array [email,name]
        'from' => null, // email address
        'account' => 'default', // The email configuration to use
        'levels' => [],
        'channels' => [],
    ];

    /**
     * To reduce the risk of issues with this, lets do some simple sanity checks
     * when the logger is created
     *
     * @param array $config
     * @return void
     */
    public function initialize(array $config) : void
    {
        if (! $this->validateEmail($this->config('to'))) {
            throw new InvalidArgumentException('Invalid Email Address for To.');
        }
        if (! $this->validateEmail($this->config('from'))) {
            throw new InvalidArgumentException('Invalid Email Address for From.');
        }
        $config = Email::config($this->config('account'));
        if (! $config) {
            throw new InvalidArgumentException(sprintf('Invalid email account `%s`', $this->config('account')));
        }
    }

    /**
     * A basic email validation to ensure params are set
     *
     * @param string|array $email
     * @return bool
     */
    protected function validateEmail($email = null) : bool
    {
        if ($email === null) {
            return false;
        }
        if (is_string($email)) {
            $email = [$email];
        }

        return (bool) filter_var($email[0], FILTER_VALIDATE_EMAIL);
    }
    /**
      * Workhorse for the logging methods
      *
      * @param string $level e.g debug, info, notice, warning, error, critical, alert, emergency.
      * @param string $message 'this is a {what}'
      * @param array $context  ['what'='string']
      * @return void
      */
    public function log(string $level, string $message, array $context = []) : void
    {
        $message = $this->format($level, $message, $context) . "\n";
        $subject = 'Log: ' . strtoupper($level);

        $this->send($subject, $message);
    }

    /**
     * Sends the email
     *
     * @param string $subject
     * @param string $message
     * @return boolean
     */
    protected function send(string $subject, string $message) : bool
    {
        $to = $this->convertToOrFrom($this->config('to'));
        $from = $this->convertToOrFrom($this->config('from'));
        /**
         * Prevent recursion
         */
        try {
            $email = Email::account($this->config('account'));
            $email->to($to[0], $to[1])
                ->from($from[0], $from[1])
                ->subject($subject)
                ->htmlMessage("<p>{$message}</p>")
                ->textMessage($message)
                ->format('both');
            $this->lastEmail = $email->send();
        } catch (\Exception $e) {
            // Don't log failures since this will create recursion
            return false;
        }

        return true;
    }

    protected function convertToOrFrom($setting)
    {
        if (is_string($setting)) {
            $setting = [$setting,null];
        } elseif (! isset($setting[1])) {
            $setting[1] = null;
        }

        return $setting;
    }
}
