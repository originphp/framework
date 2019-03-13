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

use Origin\Core\Debugger;
use Origin\Core\Logger;

class ErrorHandler
{
    /**
     * Registers the Error and Exception Handling.
     */
    public function register()
    {
        set_error_handler(array($this, 'errorHandler'));
        set_exception_handler(array($this, 'exceptionHandler'));

        if ($this->isAjax()) {
            set_exception_handler(array($this, 'ajaxExceptionHandler'));
        }
    }

    /**
     * We want to handle ajax/json exception properly (ie. not rendering html)
     *
     * Conventions which means json:
     * 1. If server requested with XMLHttpRequest (cross-domain requests might not show this jquery)
     * 2. If content_type of the request was application/json (this would have to be set manually by curl etc). Ajax
     * post also set this
     * 3. If the .json extension is detected
     *
     *
     * @todo how to set content type to json then
     * @return boolean
     */
    private function isAjax()
    {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) and $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            return true;
        }
        // check if content type requted
        if (isset($_SERVER['CONTENT_TYPE']) and $_SERVER['CONTENT_TYPE'] === 'application/json') {
            return true;
        }
        $uri = $_SERVER['REQUEST_URI'];
        if (strpos($uri, '?') !== false) {
            list($uri, $query) = explode('?', $uri);
        }
        return substr(basename($uri), -5) === '.json';
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

        if (Configure::read('debug')) {
            return $this->debugException($exception);
        }

        $this->logException($exception, $errorCode);

        http_response_code($errorCode);
        include SRC . DS . 'View' . DS . 'Error' . DS . $errorCode . '.ctp';
    }

    /**
     * Ajax and json error handler
     *
     * @param Exception $exception
     * @return void
     */
    public function ajaxExceptionHandler($exception)
    {
        $errorCode = 500;
        if ($exception->getCode() === 404) {
            $errorCode = 404;
        }
      
        if (ob_get_length()) {
            ob_end_clean();
        }

        if (Configure::read('debug')) {
            $response = ['error' => ['message' => $exception->getMessage(),'code' => $exception->getCode()]];
            echo json_encode($response);
            return true;
        }

        $this->logException($exception, $errorCode);

        http_response_code($errorCode);
        $response = ['error' => ['message' => 'An Internal Error has Occured','code' => 500]];
        if ($errorCode === 404) {
            $response = ['error' => ['message' => 'Not found','code' => 404]];
        }
        echo json_encode($response);
    }

    private function logException($exception)
    {
        $class = (new \ReflectionClass($exception))->getShortName();
        $message = $exception->getMessage();
        $line = $exception->getLine();
        $file =  str_replace(ROOT . DS, '', $exception->getFile());
     
        $message = "{$class} {$message} in {$file}:{$line}";
        $logger = new Logger('ErrorHandler');
        if ($exception instanceof \ErrorException) {
            $logger->error($message);
        } else {
            $logger->critical($message);
        }
    }

    public function debugException($exception)
    {
        $debugger = new Debugger();
        $debug = $debugger->exception($exception);

        include SRC . DS . 'View' . DS . 'Error' . DS . 'debug.ctp';

        exit();
    }
}
