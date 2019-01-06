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

    /**
     * Renders the cli exception. Initial version
     * @todo refactor to clean up code
     * @param Exception $exception
     * @return void
     */
    public function cliException($exception)
    {
        $debugger = new Debugger();
        $debug = $debugger->exception($exception);
      
        
        echo "\033[101m\033[97m {$debug['class']} \033[0;32m {$debug['message']}\033[037m\n\n";
        
        if (isset($debug['stackFrames'][0]['file'])) {
            $file = str_replace(ROOT . DS, '', $debug['stackFrames'][0]['file']);
            echo "\033[036m{$file} \033[043;37m {$debug['stackFrames'][0]['line']} \033[0;37m\n\n";
            $contents = file($debug['stackFrames'][0]['file']);
            $start = $debug['stackFrames'][0]['line'] - 5;
            $end = $debug['stackFrames'][0]['line'] + 5;
            foreach ($contents as $line => $data) {
                if ($line >= $start and $line <= $end) {
                    if ($line == $debug['stackFrames'][0]['line'] - 1) {
                        $data = $this->highlight($data);
                    }
                    echo($line + 1) .' ' .  $data;
                }
            }
        }

        // Too much info to be displayed in screen
        echo "\nPartial Stack trace\n\n";
        for ($i=1;$i<count($debug['stackFrames']);$i++) {
            if ($i > 3) {
                continue;
            }
            $class = '';
            if ($debug['stackFrames'][$i]['class']) {
                $class = $debug['stackFrames'][$i]['class'] .' ';
            }

            echo "\033[036m{$class}{$debug['stackFrames'][$i]['function']}\033[037m\n";
            if ($debug['stackFrames'][$i]['file']) {
                $file = str_replace(ROOT . DS, '', $debug['stackFrames'][$i]['file']);
                echo "{$file} \033[043m {$debug['stackFrames'][$i]['line']} \033[0;37m\n\n";
            } else {
                echo "\n";
            }
        }
     

        echo "\n\033[0m";
    }

    public function highlight($string)
    {
        return "\033[101m\033[97m{$string}\033[0;37m";
    }
}
