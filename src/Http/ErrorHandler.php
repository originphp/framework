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

/**
 * Using ajax post (e.g. $.post( url, data)), the content type for the request is 'application/x-www-form-urlencoded', instead of json. This causes
 * any exceptions to not be rendered, just getting blank screen if cross origin request since its blocked due being html
 * I think the problem is user,because not setting datatype to json (therefore Json). Framework will only
 * render json errors when json type detected.
 * Currently setting response->type json would not affect error handler
 */
namespace Origin\Http;

use Origin\Core\Debugger;
use Origin\Log\Log;
use Origin\Core\Configure;
use Origin\Exception\HttpException;
use Origin\Http\Router;
use Origin\Exception\Exception;

class FatalErrorException extends Exception
{
    public function __construct($message, $code = 500, $file = null, $line = null)
    {
        parent::__construct($message, $code);
        $this->file = $file;
        $this->line = $line;
    }
}
class ErrorHandler
{
    /**
    * Holds the level maps
    * The following error types cannot be handled with a user defined function: E_ERROR, E_PARSE, E_CORE_ERROR,
    * E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING, and most of E_STRICT raised in the
    * file where set_error_handler() is called.
    * @var array
    */
    protected $levelMap = [
        E_ERROR => 'error',
        E_PARSE => 'error',
        E_CORE_ERROR => 'error',
        E_COMPILE_ERROR => 'error',
        E_USER_ERROR => 'error',
        E_WARNING => 'warning',
        E_USER_WARNING => 'warning',
        E_COMPILE_WARNING => 'warning',
        E_RECOVERABLE_ERROR => 'warning',
        E_NOTICE => 'notice',
        E_USER_NOTICE => 'notice',
        E_DEPRECATED => 'deprecated',
        E_USER_DEPRECATED => 'deprecated',
        E_STRICT => 'strict'
    ];
    
    /**
     * Registers the Error and Exception Handling.
     */
    public function register()
    {
        set_error_handler([$this, 'errorHandler']);
        set_exception_handler([$this, 'exceptionHandler']);
        register_shutdown_function([$this,'handleFatalError']);
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
        $request = Router::request();
        if ($request) {
            return $request->type() === 'json';
        }
        return false;
    }


    /**
     * Handle Fatal Errors
     *
     * @return void
     */
    public function handleFatalError()
    {
        $error = error_get_last();
      
        // Log Message
        if ($error) {
            $this->cleanBuffer();
            $exception = new FatalErrorException($error['message'], 500, $error['file'], $error['line']);
            $this->exceptionHandler($exception);
        }
    }

    /**
     * The error handler
     * @internal pay attention to security issues
     * @param string $message error message
     * @param string $file    Filename where the error was raised
     * @param int    $line    the corresponding line number
     */
    public function errorHandler($level, $message, $file, $line)
    {
        if (error_reporting() === 0) {
            return null;
        }
        /* Original Behavior - I prefer
        if (error_reporting() !== 0) {
            throw new \ErrorException($message, 0, $level, $file, $line);
        }
        */
   
        $error = $this->levelMap[$level];

        if (Configure::read('debug')) {
            # Output
            echo sprintf('<div class="origin-error"><strong>%s:</strong> %s in <strong>%s</strong> line: <strong>%d</strong></div>', strtoupper($error), $message, $file, $line);
        }

        # Log
        $message = $message . ' in {file}, line: {line}';
        $context = ['file' => $file, 'line' => $line];
        if ($error === 'deprecated' or $error === 'strict') {
            $error = 'notice';
        }
        Log::write($error, $message, $context);
    }

    public function exceptionHandler($exception)
    {
        if ($this->isAjax()) {
            return $this->ajaxExceptionHandler($exception);
        }

        $errorCode = 500;
        if ($exception->getCode() === 404) {
            $errorCode = 404;
        }

        $this->logException($exception, $errorCode);
        $this->cleanBuffer();
        if (Configure::read('debug')) {
            return $this->debugException($exception);
        }
      
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
        $this->cleanBuffer();
        $errorCode = $exception->getCode();
        $response = ['error'=>['message'=>$exception->getMessage(),'code' => $errorCode ]];
  
        if (Configure::read('debug') === false and !$exception instanceof HttpException) {
            $errorCode = 500;
            if ($exception->getCode() === 404) {
                $errorCode = 404;
            }
            $response = ['error' => ['message' => 'An Internal Error has Occured', 'code' => 500]];
            if ($errorCode === 404) {
                $response = ['error' => ['message' => 'Not found', 'code' => 404]];
            }
        }

        $this->logException($exception, $errorCode);
        http_response_code($errorCode);
        
        echo json_encode($response);
    }

    private function logException($exception)
    {
        $class = (new \ReflectionClass($exception))->getShortName();
        $message = $exception->getMessage();
        $line = $exception->getLine();
        $file =  str_replace(ROOT . DS, '', $exception->getFile());

        $message = "{$class} {$message} in {$file}:{$line}";
        
        if ($exception instanceof \ErrorException) {
            Log::error($message);
        } else {
            Log::critical($message);
        }
    }

    /**
     * Handles the debug output
     * @param [type] $exception
     * @return void
     */
    public function debugException($exception)
    {
        $debugger = new Debugger();
        $debug = $debugger->exception($exception);

        include SRC . DS . 'View' . DS . 'Error' . DS . 'debug.ctp';

        $this->stop();
    }

    /**
     * Clean Buffer
     *
     * @internal when calling a function that does not exist on a helper from within an element it was still rendering
     * page. So to allow for nested i have added the while loop.
     */
    protected function cleanBuffer()
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }

    public function stop()
    {
        exit();
    }
}
