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

namespace Origin\Console;

use Origin\Core\Debugger;

/**
 * This is the error handler for Console
 */

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
        if (ob_get_length()) {
            ob_end_clean();
        }

        return $this->exception($exception);
    }

    /**
     * Renders the cli exception. Initial version.
     * @todo refactor to clean up code
     * @param Exception $exception
     * @return void
     */
    public function exception($exception)
    {
        $debugger = new Debugger();
        $debug = $debugger->exception($exception);
        
        extract($debug); // Make vars shorte
        $redBackground = "\033[0;101;97m";
        $yellowBackground = "\033[0;103;97m";
        $blueBackground = "\033[0;44m";
        $yellow = "\033[0;33m";
        $cyan = "\033[0;36m";
        $reset = "\033[0;37m";
        $green = "\033[0;32m";
 
        $output = "{$redBackground} {$class} {$reset}{$yellow} {$message} {$reset}\n\n";
        
        $fullBacktrace = in_array('-backtrace', $_SERVER['argv']);

        // Code Preview
        if (isset($stackFrames[0]['file'])) {
            $output .= $cyan . $this->shortenPath($stackFrames[0]['file']). " {$yellowBackground} {$stackFrames[0]['line']} {$reset}\n\n";

            $contents = file($debug['stackFrames'][0]['file']);
            $on = $debug['stackFrames'][0]['line'];
 
            foreach ($contents as $line => $data) {
                if ($line >= ($on-5) and $line <= ($on+5)) {
                    if ($line == $debug['stackFrames'][0]['line'] - 1) {
                        $data = "{$redBackground}{$data}{$reset}";
                    }
                    $output .= ($line + 1) . ' ' .  $data;
                }
            }
        }
        // Show Partial Stack Trace
        $output .="\n{$blueBackground} Stack trace {$reset}\n\n";
        foreach ($stackFrames as $i => $stackFrame) {
            if ($i== 0 or $i > 3 and !$fullBacktrace) {
                continue;
            }
            $class = $stackFrame['class']?$stackFrame['class'] .' ':'';
            $output .= "{$cyan}{$class}{$reset}{$green}{$stackFrame['function']}{$reset}\n";
            if ($stackFrame['file']) {
                $output .=  $this->shortenPath($stackFrame['file']) . " {$yellowBackground}{$stackFrame['line']}{$reset}\n\n";
            } else {
                $output .=  "\n";
            }
        }
        if ($fullBacktrace == false) {
            $output .= "{$yellow}Use -backtrace to see the full backtrace. {$reset}\n\n";
        }
        echo $output . $reset;
    }

    /**
     * Removes the /var/www from the filename
     *
     * @param string $filename
     * @return void
     */
    protected function shortenPath(string $filename)
    {
        return str_replace(ROOT . DS, '', $filename);
    }
}
