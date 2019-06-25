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

namespace Origin\Console;

use Origin\Core\Debugger;
use Origin\Console\ConsoleOutput;
use Origin\Core\Logger;

/**
 * This is the error handler for Console
 */

class ErrorHandler
{
    protected $consoleOutput = null;

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

    protected $colourStyles = [
        'redBackground' => ['color' => 'white', 'background' => 'lightRed'],
        'yellowBackground' => ['color' => 'white', 'background' => 'lightYellow'],
        'blueBackground' => ['color' => 'white', 'background' => 'blue'],
        'yellow' => ['color' => 'lightYellow'],
        'green' => ['color' => 'lightGreen'], // linux green
        'blue' => ['color' => 'blue'],
        'yellow' => ['color' => 'lightYellow'],
        'red' => ['color' => 'red'],
        'white' => ['color' => 'white'],
        'magenta' => ['color'=>'magenta'],
        'cyan' => ['color'=>'cyan']
    ];

    /**
     * Creates or gets the console output object
     *
     * @return void
     */
    protected function consoleOutput()
    {
        if ($this->consoleOutput) {
            return $this->consoleOutput;
        }
        $ConsoleOutput = new ConsoleOutput();
        foreach ($this->colourStyles as $name => $options) {
            $ConsoleOutput->styles($name, $options);
        }
        return $ConsoleOutput;
    }

    /**
     * Outputs the text using the console output object
     *
     * @param string $message
     * @param boolean $newLine
     * @return void
     */
    protected function out(string $message, $newLine = true)
    {
        return $this->consoleOutput()->write($message, $newLine);
    }
    /**
     * Renders the cli exception. Initial version.
     * @todo refactor to clean up code
     * @param Exception $exception
     * @return void
     */
    public function exceptionHandler($exception)
    {
        $debugger = new Debugger();
        $debug = $debugger->exception($exception);

        $logger = new Logger('Console');

        $message = "{$debug['class']} {$debug['message']}";
        if (isset($debug['stackFrames'][0]['file'])) {
            $filename =  str_replace(ROOT . DS, '', $debug['stackFrames'][0]['file']);
            $message .= " {$filename}:{$debug['stackFrames'][0]['line']}";
        }
        $logger->error($message);

     
        $fullBacktrace = in_array('--backtrace', $_SERVER['argv']); // (in_array('--backtrace', $_SERVER['argv']) OR defined('PHPUNIT'));
        $this->render($debug, $fullBacktrace);
    }

    /**
     * Renders a debugger array
     *
     * @param array $debug
     * @return void
     */
    public function render(array $debug, $fullBacktrace = false)
    {
        extract($debug);

        $this->out("<redBackground> {$class} </redBackground> <yellow>{$message}</yellow>\n");

        // Code Preview
        if (isset($stackFrames[0]['file'])) {
            $this->out("<cyan>" . $this->shortenPath($stackFrames[0]['file']) . "</cyan> <yellowBackground> {$stackFrames[0]['line']} </yellowBackground>\n");

            $contents = file($debug['stackFrames'][0]['file']);
            $on = $debug['stackFrames'][0]['line'] - 1;

            foreach ($contents as $line => $data) {
                if ($line >= ($on - 5) and $line <= ($on + 5)) {
                    $data = rtrim($data);
                    if ($line === $on) {
                        $data = "<redBackground>{$data}</redBackground>";
                    } else {
                        $data = "<white>{$data}</white>";
                    }
                    $this->out('<blue>' . ($line + 1) . '</blue> ' .  $data);
                }
            }
        }

        // Show Partial Stack Trace
        $this->out("\n<blueBackground> Stack Trace </blueBackground>");
        foreach ($stackFrames as $i => $stackFrame) {
            if ($i > 2 and !$fullBacktrace) {
                continue;
            }
            $class = $stackFrame['class'] ? $stackFrame['class'] . ' ' : '';
            $this->out("\n<cyan>{$class}</cyan><green>{$stackFrame['function']}</green>");

            if ($stackFrame['file']) {
                $this->out("<white>" . $this->shortenPath($stackFrame['file']) . "</white> <yellowBackground> {$stackFrame['line']} </yellowBackground>");
            }
        }
        if ($fullBacktrace === false and $i > 3) {
            $this->out("\n<yellow>Use --backtrace to see the full backtrace.</yellow>\n");
        }
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
