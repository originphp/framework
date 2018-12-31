<?php
/**
 * OriginPHP Framework
 * Copyright 2018 Jamiel Sharief.
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

class ErrorHandler
{
    /**
     * Registers the Error and Exception Handling.
     */
    public function register()
    {
        set_error_handler(array($this, 'errorHandler'));
        set_exception_handler(array($this, 'exceptionHandler'));
    }

    /**
     * Convert errors to exception but keep @ supression working.
     *
     * @param string $message error message
     * @param string $file    Filename where the error was raised
     * @param int    $line    the corresponding line number
     */
    public function errorHandler($level, $message, $file, $line)
    {
        if (error_reporting() !== 0) {
            throw new \ErrorException($message, 0, $level, $file, $line);
        }
    }

    public function exceptionHandler($exception)
    {
        $errorCode = 500;
        if ($exception->getCode() === 404) {
            $errorCode = 404;
        }
        if (ob_get_length()) {
            ob_end_clean();
        }

        if (php_sapi_name() === 'cli') {
            return $this->cliException($exception);
        }

        if (Configure::read('debug')) {
            return $this->debugException($exception);
        }

        http_response_code($errorCode);

        $message = get_class($exception)."\n";
        $message .= $exception->getMessage()."\n";
        $message .= 'Line '.$exception->getLine().' of '.$exception->getFile()."\n";
        $message .= $exception->getTraceAsString();

        Log::write('errors', $message);

        include VIEW.DS.'error'.DS.$errorCode.'.ctp';
    }

    public function debugException($exception)
    {
        $debugger = new Debugger();
        $debug = $debugger->exception($exception);

        include VIEW.DS.'error'.DS.'debug.ctp';

        exit();
    }

    public function cliException($exception)
    {
        echo $this->cliErrorAlert(get_class($exception).
      ': '.$exception->getMessage()).PHP_EOL.
      'Line no:'.$exception->getLine().' of '.$exception->getFile().PHP_EOL.
      $exception->getTraceAsString().PHP_EOL;
    }

    private function cliErrorAlert($string)
    {
        return "\033[101m\033[97m{$string}\033[0m";
    }
}
