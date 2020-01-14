<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */
declare(strict_types = 1);
namespace Origin\Console;

use Origin\Log\Log;
use Origin\Core\Debugger;

/**
 * This is the error handler for Console
 */

class ErrorHandler
{
    /**
     * ConsoleOutput object
     *
     * @var \Origin\Console\ConsoleOutput;
     */
    protected $consoleOutput = null;

    protected $colourStyles = [
        'redBackground' => ['color' => 'white', 'background' => 'lightRed'],
        'yellowBackground' => ['color' => 'white', 'background' => 'lightYellow'],
        'blueBackground' => ['color' => 'white', 'background' => 'blue'],
        'yellow' => ['color' => 'lightYellow'],
        'green' => ['color' => 'lightGreen'], // linux green
        'blue' => ['color' => 'blue'],
        'red' => ['color' => 'red'],
        'white' => ['color' => 'white'],
        'magenta' => ['color' => 'magenta'],
        'cyan' => ['color' => 'cyan'],
    ];

    /**
     * Registers the Error and Exception Handling.
     *
     * @return void
     */
    public function register() : void
    {
        set_error_handler([$this, 'errorHandler']);
        set_exception_handler([$this, 'exceptionHandler']);
    }

    /**
     * Convert errors to exception but keep @ supression working.
     *
     * @param string $message error message
     * @param string $file Filename where the error was raised
     * @param int    $line the corresponding line number
     */
    public function errorHandler($level, $message, $file, $line) : void
    {
        /**
         * @internal This is original version. Not sure how to refactor like web based handler since
         * logger console, outputs to screen as well, so it will be duplicated for errors or not show at all,
         * so this stays the same for now.
         */
        if (error_reporting() !== 0) {
            throw new \ErrorException($message, 0, $level, $file, $line);
        }
    }

    /**
     * Creates or gets the console output object
     *
     * @return \Origin\Console\ConsoleOutput
     */
    protected function consoleOutput() : ConsoleOutput
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
     * @return int
     */
    protected function out(string $message, $newLine = true) : int
    {
        return $this->consoleOutput()->write($message, $newLine);
    }
    /**
     * Renders the cli exception. Initial version.
     * @todo refactor to clean up code
     * @param \Exception $exception
     * @return void
     */
    public function exceptionHandler($exception) : void
    {
        $debugger = new Debugger();
        $debug = $debugger->exception($exception);

        $message = "{$debug['class']} {$debug['message']}";
        if (isset($debug['stackFrames'][0]['file'])) {
            $filename = str_replace(ROOT . DS, '', $debug['stackFrames'][0]['file']);
            $message .= " {$filename}:{$debug['stackFrames'][0]['line']}";
        }
        Log::error($message);

        $fullBacktrace = in_array('--verbose', $_SERVER['argv']); // (in_array('--backtrace', $_SERVER['argv']) OR defined('PHPUNIT'));
        $this->render($debug, $fullBacktrace);
        $this->exit();
    }

    /**
     * Renders a debugger array
     *
     * @param array $debug
     * @return void
     */
    public function render(array $debug, $fullBacktrace = false) : void
    {
        extract($debug);

        $this->out("<redBackground> {$class} </redBackground> <yellow>{$message}</yellow>\n");

        // Code Preview
        if (isset($stackFrames[0]['file'])) {
            $this->out('<cyan>' . $this->shortenPath($stackFrames[0]['file']) . "</cyan> <yellowBackground> {$stackFrames[0]['line']} </yellowBackground>\n");

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
            if ($i > 2 and ! $fullBacktrace) {
                continue;
            }
            $class = $stackFrame['class'] ? $stackFrame['class'] . ' ' : '';
            $this->out("\n<cyan>{$class}</cyan><green>{$stackFrame['function']}</green>");

            if ($stackFrame['file']) {
                $this->out('<white>' . $this->shortenPath($stackFrame['file']) . "</white> <yellowBackground> {$stackFrame['line']} </yellowBackground>");
            }
        }
        if ($fullBacktrace === false and $i > 3) {
            $this->out("\n<yellow>Use --verbose to see the full backtrace.</yellow>\n");
        }
    }

    /**
     * Removes the /var/www from the filename
     *
     * @param string $filename
     * @return string
     */
    protected function shortenPath(string $filename) : string
    {
        return str_replace(ROOT . DS, '', $filename);
    }

    /**
     * Sends the exit command with the exit code for for an error
     *
     * @return void
     */
    protected function exit(int $exitCode = 1) : void
    {
        // @codeCoverageIgnoreStart
        exit($exitCode);
        // @codeCoverageIgnoreEnd
    }
}
