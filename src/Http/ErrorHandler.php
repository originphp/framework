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

/**
 * Using ajax post (e.g. $.post( url, data)), the content type for the request is 'application/x-www-form-urlencoded', instead of json. This causes
 * any exceptions to not be rendered, just getting blank screen if cross origin request since its blocked due being html
 * I think the problem is user,because not setting datatype to json (therefore Json). Framework will only
 * render json errors when json type detected.
 * Currently setting response->type json would not affect error handler
 */

namespace Origin\Http;

use Origin\Log\Log;
use Origin\Core\Config;
use Origin\Core\Debugger;
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
     * If an error has been handled
     *
     * @var boolean
     */
    protected $handled = false;
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
        E_STRICT => 'strict',
    ];

    /**
     * Registers the Error and Exception Handling.
     *
     * @return void
     */
    public function register(): void
    {
        set_error_handler([$this, 'errorHandler']);
        set_exception_handler([$this, 'exceptionHandler']);
        register_shutdown_function([$this, 'handleFatalError']);
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
     * @return boolean
     */
    protected function isAjax(): bool
    {
        $result = false;
        $request = Router::request();
        if ($request) {
            $result = ($request->ajax() or $request->type() === 'json');
        }

        return $result;
    }

    /**
     * Shutdown function to check for fatal errors
     *
     * @return void
     */
    public function handleFatalError(): void
    {
        $error = error_get_last();
        if (is_array($error) and in_array($error['type'], [E_PARSE,E_ERROR,E_USER_ERROR])) {
            $this->fatalErrorHandler($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }

    /**
        * The fatal error handler
        *
        * @internal pay attention to security issues
        * @param string $message error message
        * @param string $file  Filename where the error was raised
        * @param int  $line the corresponding line number
        * @return void
        */
    public function fatalErrorHandler(int $level, string $message, string $file, int $line): void
    {
        $exception = new FatalErrorException($message, 500, $file, $line);
        $this->exceptionHandler($exception);
    }

    /**
     * The error handler
     *
     * @internal pay attention to security issues
     * @param string $message error message
     * @param string $file    Filename where the error was raised
     * @param int    $line    the corresponding line number
     * @return void
     */
    public function errorHandler(int $level, string $message, string $file, int $line): void
    {
        if (error_reporting() === 0) {
            return;
        }
        /* Original Behavior - I prefer
        if (error_reporting() !== 0) {
            throw new \ErrorException($message, 0, $level, $file, $line);
        }
        */

        $error = $this->levelMap[$level];

        if (Config::read('debug')) {
            # Output
            echo sprintf('<div class="origin-error"><strong>%s:</strong> %s in <strong>%s</strong> line: <strong>%d</strong></div>', strtoupper($error), $message, $file, $line);
        }

        # Log
        if ($error === 'deprecated' or $error === 'strict') {
            $error = 'notice';
        }
        Log::write($error, $message . ' in {file}, line: {line}', [
            'file' => $file, 'line' => $line,
        ]);
    }

    /**
     * Undocumented function
     *
     * @param Exception $exception
     * @return void
     */
    public function exceptionHandler($exception) : void
    {
        $errorCode = 500;
        if ($exception->getCode() === 404) {
            $errorCode = 404;
        }

        $this->logException($exception, $errorCode);
        $this->cleanBuffer();

        /**
         * Display debug backtrace
         */
        if (Config::read('debug') === true and ! $this->isAjax()) {
            $this->debugExceptionHandler($exception);
        } else {
            $renderer = new ExceptionRenderer(Router::request());
            $response = $renderer->render($exception, Config::read('debug'));
            $this->sendResponse($response->body(), $response->statusCode());
        }
        
        $this->stop();
    }

    /**
     * Logs The exception
     *
     * @param Exception $exception
     * @return void
     */
    protected function logException($exception): void
    {
        $class = (new \ReflectionClass($exception))->getShortName();
        $message = $exception->getMessage();
        $line = $exception->getLine();
        $file = str_replace(ROOT . DS, '', $exception->getFile());

        $message = "{$class} {$message} in {$file}:{$line}";

        if ($exception instanceof \ErrorException) {
            Log::error($message);
        } else {
            Log::critical($message);
        }
    }

    /**
     * Handles the debug output
     *
     * @param Exception $exception
     * @return void
     */
    public function debugExceptionHandler($exception): void
    {
        $debugger = new Debugger();
        $debug = $debugger->exception($exception);
          
        ob_start();
        include SRC . DS . 'Http' . DS . 'View' . DS . 'Error' . DS . 'debug.ctp';
        $response = ob_get_clean();
      
        $this->sendResponse($response);
    }

    /**
     * Clean Buffer
     * @codeCoverageIgnore
     * @internal when calling a function that does not exist on a helper from within an element it was still rendering
     * page. So to allow for nested i have added the while loop.
     *
     * @return void
     */
    protected function cleanBuffer(): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }

    /**
     * A wrapper to make it easier to test
     * @codeCoverageIgnore
     * @return void
     */
    protected function sendResponse(string $response = null, int $statusCode = 200) : void
    {
        http_response_code($statusCode);
        echo $response;
    }

    /**
     * A wrapper to make it easier to test
     * @codeCoverageIgnore
     * @return void
     */
    public function stop(): void
    {
        exit();
    }
}
