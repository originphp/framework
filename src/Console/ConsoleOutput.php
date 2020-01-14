<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright    Copyright (c) Jamiel Sharief
 * @link         https://www.originphp.com
 * @license      https://opensource.org/licenses/mit-license.php MIT License
 */
declare(strict_types = 1);
namespace Origin\Console;

use Origin\Core\Exception\InvalidArgumentException;

class ConsoleOutput
{
    /**
     * Holds the stream for writing
     *
     * @var resource
     */
    protected $stream = null;

    const RAW = 0;
    const PLAIN = 1;
    const COLOR = 2;

    protected $mode = SELF::COLOR;

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
        'white' => 97,
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
        'white' => 107,
    ];

    protected $options = [
        'reset' => 0, // reset all
        'bold' => 1,
        'underline' => 4,
        'blink' => 5,
        'reverse' => 7,
    ];

    protected $optionsUnset = [
        'reset' => 0, // reset all
        'bold' => 22,
        'underline' => 24,
        'blink' => 25,
        'reverse' => 27,
    ];

    protected $styles = [
        'exception' => ['color' => 'white','background' => 'lightRed'],

        # Notifications
        /**
         * POSIX Levels. Works with logger as well
         */
        'emergency' => ['color' => 'white','background' => 'lightRed','blink' => true],
        'alert' => ['color' => 'white','background' => 'lightRed'],
        'critical' => ['color' => 'white','background' => 'lightRed'],
        'error' => ['color' => 'red'],
        'warning' => ['color' => 'yellow'],
        'notice' => ['color' => 'cyan'],
        'info' => ['color' => 'blue'],
        'debug' => ['color' => 'white'],
       
        'success' => ['color' => 'lightGreen'],

        # Standardize Outputs
        'heading' => ['color' => 'lightYellow'],
        'text' => ['color' => 'white'],
        'code' => ['color' => 'lightGreen'],
      
        # Standard Colors which make things just easier
        'green' => ['color' => 'lightGreen'], // linux green
        'blue' => ['color' => 'blue'],
        'yellow' => ['color' => 'lightYellow'],
        'red' => ['color' => 'red'],
        'white' => ['color' => 'white'],
        'magenta' => ['color' => 'magenta'],
        'cyan' => ['color' => 'cyan'],
    ];

    /**
     * Constructs a new instance
     * @param string $stream fopen stream php://stdout
     */
    public function __construct(string $stream = 'php://stdout')
    {
        $this->stream = fopen($stream, 'r');
        // Check that Ansi Escape Sequences are supported
        if (! $this->supportsAnsi()) {
            $this->mode = SELF::PLAIN;
        }
    }
    
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Checks for Ansi Support
     *
     * @return bool
     */
    public function supportsAnsi() : bool
    {
        return function_exists('posix_isatty') and posix_isatty($this->stream);
    }

    /**
     * Sets and gets the mode for output
     *
     * @param integer $mode
     * @return int|void
     */
    public function mode(int $mode = null)
    {
        if ($mode === null) {
            return $this->mode;
        }
        if (! in_array($mode, [self::RAW,self::PLAIN, self::COLOR])) {
            throw new InvalidArgumentException(sprintf('Invalid mode %s', $mode));
        }
        $this->mode = $mode;
    }

    /**
     * Prepares data and writes to stream
     *
     * @param string|array $data
     * @return int
     */
    public function write($data, $newLine = true) : int
    {
        if (is_array($data)) {
            $data = implode("\n", $data);
        }
        
        $data = $this->styleText($data);

        if ($newLine) {
            $data .= "\n";
        }

        fwrite($this->stream, $data);

        return strlen($data);
    }

    /**
     * Styles the text
     *
     * @param string $text
     * @return string
     */
    public function styleText(string $text) : string
    {
        if ($this->mode === SELF::RAW) {
            return $text;
        }

        if ($this->mode === SELF::PLAIN) {
            $tags = array_keys($this->styles);

            return preg_replace('/<\/?(' . implode('|', $tags) . ')>/', '', $text);
        }

        return $this->parseTags($text); // color default
    }

    /**
     * Close the stream
     *
     * @return void
     */
    public function close() : void
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
    }

    /**
    * Formats the text by parsing tags
    * @param  string $string text
    * @return string text
    */
    public function parseTags($string) : string
    {
        $regex = '/<([a-z0-9]+)>(.*?)<\/(\1)>/ims';
        if (preg_match_all($regex, $string, $matches)) {
            foreach ($matches[1] as $key => $tag) {
                $text = $matches[2][$key];
               
                # Handle Nested Colors, preserving previous colors
                if (preg_match($regex, $text, $match)) {
                    $nestedText = "</{$tag}>{$match[0]}<{$tag}>" ;  // Wrap in parent tags (reverse)
                    $string = str_replace($match[0], $nestedText, $string);
                    $string = $this->parseTags($string);
                }
              
                $string = str_replace("<{$tag}>{$text}</{$tag}>", $this->style($tag, $text), $string);
            }
        }
        
        return $string;
    }

    /**
     * Generates the styled ansi string
     *
     * @param string $tag
     * @param string $text
     * @return string
     */
    protected function style(string $tag, string $text) : string
    {
        if (isset($this->styles[$tag]) === false) {
            return "<{$tag}>{$text}</{$tag}>";
        }
        $settings = $this->styles[$tag];
        
        return $this->color($text, $settings);
    }
    /**
     * Colors a string
     *
     * @param string $text  'some random text'
     * @param array $settings ['color'=>'blue','background'=>'red','blink'=>true]
     * @return string
     */
    public function color(string $text, array $settings) : string
    {
        $set = [];
        $unset = [];
        if (isset($settings['color']) and isset($this->foregroundColors[$settings['color']])) {
            $set[] = $this->foregroundColors[$settings['color']];
            $unset[] = 39;
        }
        if (isset($settings['background']) and isset($this->backgroundColors[$settings['background']])) {
            $set[] = $this->backgroundColors[$settings['background']];
            $unset[] = 49;
        }
        unset($settings['color'], $settings['background']);
        foreach ($settings as $option => $value) {
            if ($value and isset($this->options[$option])) {
                $set[] = $this->options[$option];
                $unset[] = $this->optionsUnset[$option];
            }
        }
        if (empty($set)) {
            return $text;
        }

        return "\033[" . implode(';', $set) . 'm' . $text . "\033[" . implode(';', $unset) . 'm';
    }
    
    /**
     * Sets or modifies existing styles
     *  $styles = $ConsoleOutput->styles();
     *  $style = $ConsoleOutput->style('primary');
     *  $ConsoleOutput->style('primary',$styleArray);
     *  $ConsoleOutput->style('primary',false);
     *
     * @param string $name
     * @param array $values array('color' => 'white','background'=>'blue','bold' => true) or false to delete
     * @return bool|array|null
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
}
