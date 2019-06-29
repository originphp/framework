<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright    Copyright (c) Jamiel Sharief
 * @link         https://www.originphp.com
 * @license      https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Console;

use Origin\Console\Exception\ConsoleException;

class ConsoleIo
{
    /**
     * Output Stream.
     *
     * @var \Origin\Console\ConsoleOutput
     */
    protected $stdout = null;

    /**
     * Error Stream.
     *
     * @var \Origin\Console\ConsoleOutput
     */
    protected $stderr = null;

    /**
     * Input Stream.
     *
     * @var \Origin\Console\ConsoleInput
     */
    protected $stdin = null;

    /**
     * For the status function.
     *
     * @var array
     */
    protected $statusCodes = [
        'ok' => 'green',
        'error' => 'red',
        'ignore' => 'yellow',
        'skipped' => 'cyan',
        'started' => 'green',
        'stopped' => 'yellow',
    ];

    /**
     * Character length of
     *
     * @var int
     */
    protected $lastWrittenLength = null;

    public function __construct(ConsoleOutput $out = null, ConsoleOutput $err = null, ConsoleInput $in = null)
    {
        if ($out === null) {
            $out = new ConsoleOutput('php://stdout');
        }
        if ($err === null) {
            $err = new ConsoleOutput('php://stderr');
        }
        if ($in === null) {
            $in = new ConsoleInput('php://stdin');
        }
        $this->stdout = $out;
        $this->stderr = $err;
        $this->stdin = $in;
    }

    /**
     * Outputs line or lines to the stdout adding \n to each line.
     *
     * @param string|array $message
     */
    public function out($message)
    {
        $this->lastWrittenLength = $this->stdout->write($message, true);
    }

    /**
     * Writes to the output without adding new lines.
     *
     * @param string|array $message
     */
    public function write($message)
    {
        $this->lastWrittenLength =  $this->stdout->write($message, false);
    }

    /**
     * Outputs line or lines to the stderr.
     *
     * @param string|array $message
     */
    public function err($message)
    {
        $this->stderr->write($message);
    }

    /**
     * Overwrites the last text. Does not work if used new line after text
     *
     * $io->write('downloading...');
     * $io->overwrite('completed');
     *
     * @param string|array $message
     * @return void
     */
    public function overwrite($message, $newLine = true)
    {
        if (is_array($message)) {
            $message = implode("\n", $message);
        }
        $this->stdout->write("\033[{$this->lastWrittenLength}D", false);
    
        $difference = strlen($message) - $this->lastWrittenLength;
        if ($difference > 0) {
            $message .= str_repeat(' ', $difference);
        }
        if ($newLine) {
            $message .= "\n";
        }
        $this->write($message);
    }

    
    /**
     * A Title style.
     *
     * @param string $heading
     */
    public function title(string $title, string $style ='heading')
    {
        $this->out("<{$style}>{$title}</{$style}>");
        $this->out("<{$style}>".str_repeat('=', strlen($title))."</{$style}>");
        $this->nl();
    }

    /**
     * A heading style.
     *
     * @param string $heading1
     */
    public function heading(string $heading, string $style ='heading')
    {
        $this->out("<{$style}>{$heading}</{$style}>");
        $this->out("<{$style}>".str_repeat('-', strlen($heading))."</{$style}>");
        $this->nl();
    }

    /**
     * This ouput texts for use with heading,title etc. Text will automatically be indented.
     *
     * @param string|array $text
     * @param integer $indent
     * @return void
     */
    public function text($text, int $indent=2)
    {
        $text = (array) $text;
        foreach ($text as $line) {
            $this->out(str_repeat(' ', $indent).$line);
        }
    }

    /**
     * Draws table.
     *
     * @param array $array
     * @param bool  $headers wether first row is headers
     */
    public function table(array $array, $headers = true)
    {
        // Calculate width of each column
        $widths = [];
        foreach ($array as $rowIndex => $row) {
            $maxColumnWidth = 0;
            foreach ($row as $columnIndex => $cell) {
                if (!isset($widths[$columnIndex])) {
                    $widths[$columnIndex] = 0;
                }
                $width = strlen($cell) + 4;
                if ($width > $widths[$columnIndex]) {
                    $widths[$columnIndex] = $width;
                }
            }
        }

        $out = [];
        $seperator = '';
        foreach ($array[0] as $i => $cell) {
            $seperator .= str_pad('+', $widths[$i], '-', STR_PAD_RIGHT);
        }
        $seperator .= '+';
        $out[] = $seperator;

        if ($headers) {
            $headers = '|';
            foreach ($array[0] as $i => $cell) {
                $headers .= ' '.str_pad($cell, $widths[$i] - 2, ' ', STR_PAD_RIGHT).'|';
            }
            $out[] = $headers;
            $out[] = $seperator;
            array_shift($array);
        }

        foreach ($array as $row) {
            $cells = '|';
            foreach ($row as $i => $cell) {
                $cells .= ' '.str_pad($cell, $widths[$i] - 2, ' ', STR_PAD_RIGHT).'|';
            }
            $out[] = $cells;
        }
        $out[] = $seperator;
        $this->out($out);
    }

    /**
     * Generates a list of list item.
     *
     * @param string|array $elements 'buy milk' or ['buy milk','read the paper']
     * @param string $bullet e.g * or -
     * @param integer $indent indent amount
     * @return void
     */
    public function list($elements, string $bullet = '*', int $indent=2)
    {
        foreach ((array) $elements as $element) {
            $this->out(str_repeat(' ', $indent).$bullet.' '.$element);
        }
    }

    /**
     * Formats a string by using array of options. such as color,background.
     *
     * @param string $text
     * @param array  $options (background,color,blink=true etc)
     *
     * @return string
     */
    public function format(string $text, array $options = [])
    {
        return $this->stdout->color($text, $options);
    }

    /**
     * Displays a info.
     *
     * @param string|array $messages line or array of lines
     * @param array        $options  (background,color,blink,bold,underline)
     */
    public function info($messages, array $options = [])
    {
        $options += ['background' => 'blue', 'color' => 'white', 'bold' => true];
        $this->highlight($messages, $options);
    }

    /**
     * Displays a success block or alert.
     *
     * @param string|array $messages line or array of lines
     * @param array        $options  (background,color,blink,bold,underline)
     */
    public function success($messages, array $options = [])
    {
        $options += ['background' => 'green', 'color' => 'white', 'bold' => true];
        $this->highlight($messages, $options);
    }

    /**
    * Displays a warning block or alert to stderr out
    *
    * @param string|array $messages line or array of lines
    * @param array        $options  (background,color,blink,bold,underline)
    */
    public function warning($messages, array $options = [])
    {
        $options += ['background' => 'yellow', 'color' => 'black', 'bold' => true];
        foreach ((array) $messages as $message) {
            $string = $this->format($message, $options);
            $this->stderr->write($string);
        }
    }

    /**
     * Displays an error block or alert.
     *
     * @param string|array $messages line or array of lines
     * @param array        $options  (background,color,blink,bold,underline)
     */
    public function error($messages, array $options = [])
    {
        $options += ['background' => 'lightRed', 'color' => 'white', 'bold' => true];
        foreach ((array) $messages as $message) {
            $string = $this->format($message, $options);
            $this->stderr->write($string);
        }
    }

    /**
     * Draws a progress bar.
     *
     * thank you!
     * @param integer $value
     * @param integer $max
     * @param array $options (color) e.g. [color=>cyan]
     * @return void
     * @see http://ascii-table.com/ansi-escape-sequences-vt-100.php
     */
    public function progressBar(int $value, int $max, array $options=[])
    {
        $options += ['color'=>'green'];
        
        $progressBar = '';
        $full = '#';
        $empty = ' ';

        $percent = floor(($value / $max) * 100);
        $left = 100 - $percent;

        $ansi = $this->stdout->supportsAnsi();

        if ($ansi) {
            $full = $this->format(' ', ['background'=>$options['color']]);
            $empty = "\033[30;40m \033[0m";
        }
        if ($percent) {
            $progressBar = str_repeat($full, floor($percent / 2));
        }
        if ($left) {
            if ($left %2 !== 0) {
                $left ++;
            }
            $progressBar .= str_repeat($empty, floor($left / 2));
        }
        
        $progress = $percent . '%';
        if ($ansi) {
            $progress = $this->format($progress, $options);
        } else {
            $progressBar = "[{$progressBar}]";
        }
        
        $this->write("\r{$progressBar} {$progress}");
        
        if ($percent == 100) {
            $this->write("\r" . str_repeat($empty, 60) . "\r"); // should be \n or \r
        }
    }

    /**
     * Highlights some text.
     *
     * @param string $message
     * @param string $bgColor
     * @param string $textColor
     */
    public function highlight($messages, array $options = [])
    {
        $options += ['background' => 'black', 'color' => 'white'];

        if (!is_array($messages)) {
            $messages = [$messages];
        }
        foreach ($messages as $message) {
            $this->writeFormatted($message, $options);
        }
    }

    /**
     * Generates a colourful padded alert.
     *
     * @param string|array $messages
     * @param array        $options
     */
    public function alert($messages, array $options = [])
    {
        $options += ['background' => 'black', 'color' => 'white'];

        $this->nl();

        if (!is_array($messages)) {
            $messages = [$messages];
        }

        $this->writeFormatted(str_repeat(' ', 80), $options);
        foreach ($messages as $message) {
            $this->writeFormatted('  '.str_pad($message, 80 - 2, ' ', STR_PAD_RIGHT), $options);
        }
        $this->writeFormatted(str_repeat(' ', 80), $options);
        $this->nl();
    }

    /**
     * Wraps text in a colourful block.
     *
     * @param string|array $messages
     * @param array        $options
     */
    public function block($messages, array $options = [])
    {
        $options += ['background' => 'black', 'color' => 'white', 'padding' => 4];

        $center = STR_PAD_RIGHT;
  
        $this->nl();

        if (!is_array($messages)) {
            $messages = [$messages];
        }
        $maxLength = $this->getMaxLength($messages) + ($options['padding'] * 2);

        $this->writeFormatted(str_repeat(' ', $maxLength), $options);
        foreach ($messages as $message) {
            $padding = str_repeat(' ', $options['padding']);
            $message = $padding.$message.$padding;
            $this->writeFormatted(str_pad($message, $maxLength, ' ', $center), $options);
        }
        $this->writeFormatted(str_repeat(' ', $maxLength), $options);
        $this->nl();
    }

    /**
     * Outputs new lines.
     *
     * @param int $count number of newlines
     */
    public function nl($count = 1)
    {
        $this->stdout->write(str_repeat("\n", $count), false);
    }

    /**
     * Clears the screen.
     */
    public function clear()
    {
        $this->stdout->write("\033c", false);
    }

    /**
     * Asks the user a question and returns the value (or default if set).
     *
     * @param string $prompt  The question to ask
     * @param string $default default value if user presses enter
     *
     * @return string
     */
    public function ask(string $prompt, string $default = null)
    {
        $input = '';
        if ($default) {
            $prompt .= " [{$default}]";
        }

        $this->stdout->write("\033[32;49m".$prompt);
        $this->stdout->write("\033[97;49m> ", false);
        $input = $this->stdin->read();
        if ($input === '' and $default) {
            return $default;
        }

        $this->stdout->write("\033[0m"); // reset + line break
        return $input;
    }

    /**
     * Asks the user a question and returns the value (or default if set).
     *
     * @param string $prompt  The question to ask
     * @param array  $options ['yes','no']
     * @param string $default default value if user presses enter
     */
    public function askChoice(string $prompt, array $options, string $default = null)
    {
        $input = $defaultString = '';
        $optionsString = implode('/', $options);
        if ($default) {
            $defaultString = "[{$default}]";
        }
        $extra = " ({$optionsString}) {$defaultString}";

        // Check both uppercase and lower case input
        $options = array_merge(
            array_map('strtolower', $options),
            array_map('strtoupper', $options)
        );
       
      
        while ($input === '' or !in_array($input, $options)) {
            $this->stdout->write("\033[32;49m{$prompt} {$extra}");
            $this->stdout->write("\033[97;49m> ", false);
            $input = $this->stdin->read();
            if ($input === '' and $default) {
                return $default;
            }
            # Catch out errors caused by not sending data via ConsoleIntegratioTest::exec
            if ($input === null) {
                throw new ConsoleException(sprintf('No input for `%s`', $prompt));
            }
        }
        $this->stdout->write("\033[0m"); // reset + line break
        return $input;
    }

    /**
     * Creates a file, and asks wether to overwrite.
     *
     * @param string $filename
     * @param string $contents
     * @param bool   $forceOverwrite
     * @return bool
     */
    public function createFile(string $filename, string $contents, $forceOverwrite = false) : bool
    {
        if (file_exists($filename) and $forceOverwrite !== true) {
            $this->warning("File {$filename} already exists");
            $input = $this->askChoice('Do you want to overwrite?', ['y', 'n'], 'n');
            if ($input === 'n') {
                return false;
            }
        }

        try {
            $directory = dirname($filename);
            if (!file_exists($directory)) {
                mkdir($directory, 0777, true);
            }
            return (bool) file_put_contents($filename, $contents);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Displays a status.
     *
     * @param string $type    e.g. ok, error, ignore
     * @param string $message
     */
    public function status(string $status, string $message)
    {
        if (!isset($this->statusCodes[$status])) {
            throw new ConsoleException(sprintf('Unkown status %s', $status));
        }
        $color = $this->statusCodes[$status];
        $status = strtoupper($status);
        $this->out("<white>[</white> <{$color}>{$status}</{$color}> <white>] {$message}</white>");
    }

    /**
     * Get the max length of a an array of lines.
     *
     * @param array $lines
     *
     * @return int
     */
    protected function getMaxLength(array $lines)
    {
        $maxLength = 0;
        foreach ($lines as $line) {
            $length = strlen($line);
            if ($length > $maxLength) {
                $maxLength = $length;
            }
        }

        return $maxLength;
    }

    /**
    * Sets or modifies existing styles
    *  $styles = $io->styles();
    *  $style = $io->styles('primary');
    *  $io->styles('primary',$styleArray);
    *  $io->styles('primary',false);
    *
    * @param string $name
    * @param array $values array('color' => 'white','background'=>'blue','bold' => true) or false to delete
    * @return bool|array|null
    */
    public function styles(string $name = null, $values = null)
    {
        $this->stderr->styles($name, $values);
        return $this->stdout->styles($name, $values);
    }

    /**
     * Returns the error output object
     *
     * @return \Origin\Console\ConsoleOutput;
     */
    public function stderr()
    {
        return $this->stderr;
    }
    /**
    * Returns the output object
    *
    * @return \Origin\Console\ConsoleOutput;
    */
    public function stdout()
    {
        return $this->stdout;
    }
    /**
     * Returns the input object
     *
     * @return \Origin\Console\ConsoleInput;
     */
    public function stdin()
    {
        return $this->stdin;
    }

    /**
     * Formats and writes a line by using array of options. such as color,background.
     *
     * @param string $text
     * @param array  $options
     */
    protected function writeFormatted(string $text, array $options = [])
    {
        $string = $this->format($text, $options);
        $this->stdout->write($string);
    }
}
