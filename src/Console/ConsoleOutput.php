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

class ConsoleOutput
{
    /**
     * Holds the stream for writing
     *
     * @var resource
     */
    protected $stream = null;

    protected $foregroundColors = [
        'default' => 39,
        'black' => 30,
        'red' => 31,
        'green' => 32,
        'yellow' => 33,
        'blue' => 34,
        'magenta' => 35,
        'cyan' => 36,
        'lightGrey' => 37,
        'darkGrey' => 90,
        'lightRed' => 91,
        'lightGreen' => 92,
        'lightYellow' => 93,
        'lightBlue' => 94,
        'lightMagenta' => 95,
        'lightCyan' => 96,
        'white' => 97
    ];

    protected $backgroundColors = [
        'default' => 49,
        'black' => 40,
        'red' => 41,
        'green' => 42,
        'yellow' => 43,
        'blue' => 44,
        'magenta' => 45,
        'cyan' => 46,
        'lightGrey' => 37,
        'darkGrey' => 100,
        'lightRed' => 101,
        'lightGreen' => 102,
        'lightYellow' => 103,
        'lightBlue' => 104,
        'lightMagenta' => 105,
        'lightCyan' => 106,
        'white' => 107
    ];

    protected $options = [
        'reset' => 0, // reset all
        'bold' => 1,
        'underline' => 4,
        'blink' => 5,
        'reverse' => 7,
    ];

    protected $styles = [
        'debug' => ['text'=>'cyan'],
        'info' => ['text' => 'lightYellow'],
        'notice' => ['text' => 'blue'],
        'warning' => ['text' => 'yellow'],
        'error' => ['text' => 'white','background'=>'lightRed'],
        'critical' => ['text' => 'white','background'=>'lightRed'],
        'alert' => ['text' => 'white','background'=>'lightRed'],
        'success' => ['text' => 'white','background'=>'lightBlue'],
        'comment' => ['text' => 'white'], // optional text
        'prompt' => ['text' => 'blue'],
        'green' => ['text' => 'lightGreen'], // linux green
        'blue' => ['text' => 'blue'],
        'yellow' => ['text' => 'yellow'],
        'red' => ['text' => 'red'],
        'white' => ['text' => 'white'],
        'magenta' => ['text'=>'magenta'],
        'cyan' => ['text'=>'cyan']
    ];


    /**
     * Constructs a new instance
     * @param string $stream fopen stream php://stdout
     */
    public function __construct(string $stream ='php://stdout')
    {
        $this->stream = fopen($stream, 'w');
    }
    
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Writes to the stream
     *
     * @param string $data
     * @return void
     */
    public function write(string $data)
    {
        $data = $this->parseTags($data);
        return fwrite($this->stream, $data);
    }

    /**
     * Close the stream
     *
     * @return void
     */
    public function close()
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
    }

    /**
    * Fromats the text by parsing tags
    * @param  string $string text
    * @return string        text
    */
    public function parseTags($string)
    {
        if (preg_match_all('/<([a-z0-9-_]+)>(.*?)<\/([a-z0-9-_]+)>/ims', $string, $matches)) {
            foreach ($matches[1] as $key => $tag) {
                $text = $matches[2][$key];

                $string = str_replace("<{$tag}>{$text}</{$tag}>", $this->style($tag, $text), $string);
            }
        }
        
        return $string;
    }

    /**
     * Generates the styled ansi string
     */
    protected function style($tag, $text)
    {
        if (isset($this->styles[$tag]) === false) {
            return "<{$tag}>{$text}</{$tag}>";
        }
        $settings = $this->styles[$tag];
     
        $ansi = [];
        if (isset($settings['text']) and isset($this->foregroundColors[$settings['text']])) {
            $ansi[] = $this->foregroundColors[$settings['text']];
        }
        if (isset($settings['background']) and isset($this->backgroundColors[$settings['background']])) {
            $ansi[] = $this->backgroundColors[$settings['background']];
        }
        unset($settings['text'], $settings['background']);
        foreach ($settings as $option => $value) {
            if ($value) {
                $ansi[] = $this->options[$option];
            }
        }
        
        return "\033[" . implode(';', $ansi) . 'm' . $text . "\033[0m";
    }

    /**
     * Sets or modifies existing styles
     *  $styles = $ConsoleOutput->styles();
     *  $style = $ConsoleOutput->style('primary');
     *  $ConsoleOutput->style('primary',$styleArray);
     *  $ConsoleOutput->style('primary',false);
     *
     * @param string $name
     * @param array $values array('text' => 'white','background'=>'blue','bold' => true) or false to delete
     * @return void
     */
    public function styles(string $name = null, $values = null)
    {
        if ($name === null) {
            return $this->styles;
        }
        if ($values === null) {
            if (isset($this->styles[$name])) {
                return $this->styles[$name];
            }
            return null;
        }
        if ($values === false) {
            unset($this->styles[$name]);
            return true;
        }
        $this->styles[$name] = $values;
        return true;
    }

    /**
     * Displays an error message
     *
     * @param string $title Name of error
     * @param string $message An optional description
     * @return void
     */
   

    public function debug(string $title, string $message=null)
    {
        $msg = $this->buildMessage('debug', $title, $message);
        $this->write($msg);
    }

    public function info(string $title, string $message=null)
    {
        $msg = $this->buildMessage('info', $title, $message);
        $this->write($msg);
    }

    public function notice(string $title, string $message=null)
    {
        $msg = $this->buildMessage('notice', $title, $message);
        $this->write($msg);
    }
    public function warning(string $title, string $message=null)
    {
        $msg = $this->buildMessage('warning', $title, $message);
        $this->write($msg);
    }

    public function success(string $title, string $message=null)
    {
        $msg = $this->buildMessage('success', $title, $message);
        $this->write($msg);
    }

    public function error(string $title, string $message=null)
    {
        $msg = $this->buildMessage('error', $title, $message);
        $this->write($msg);
    }

    public function critical(string $title, string $message=null)
    {
        $msg = $this->buildMessage('critical', $title, $message);
        $this->write($msg);
    }

   
    /*

        'info' => ['text' => 'yellow'],
        'notice' => ['text' => 'blue'],
        'warning' => ['text' => 'yellow'],
        'error' => ['text' => 'white','background'=>'lightRed'],
        'critical' => ['text' => 'white','background'=>'lightRed'],
        'alert' => ['text' => 'white','background'=>'lightRed'],
        */

    /**
     * Undocumented function
     *
     * @param string $type Type of message error, warning, critical, debug etc.
     * @param string $title main error
     * @param string $message an optional description
     * @return string
     */
    protected function buildMessage(string $type, string $title, string $message=null) : string
    {
        $result = "<{$type}>" . strtoupper($type).  ":</{$type}> <info>{$title}</info>\n";
        if ($message) {
            $result .= "<comment>{$message}</comment>\n";
        }
        return $result ;
    }
}
