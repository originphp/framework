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
        'exception' => ['color' => 'white','background'=>'lightRed'],

        # Notifications
        'debug' => ['color' => 'white'], 
        'success' => ['color' => 'lightGreen'], 
        'error' => ['color' => 'red'],
        'warning'=> ['color' => 'yellow'],
        'notice' => ['color' => 'cyan'],
        'alert' => ['color' => 'white','background'=>'lightRed'],

        # Standardize Outputs
        'heading' => ['color'=>'lightYellow'],
        'text' => ['color'=>'white'],
        'info' => ['color' => 'blue'],
        'code' => ['color' => 'lightGreen'],

        # Standard Colors which make things just easier
        'green' => ['color' => 'lightGreen'], // linux green
        'blue' => ['color' => 'blue'],
        'yellow' => ['color' => 'lightYellow'],
        'red' => ['color' => 'red'],
        'white' => ['color' => 'white'],
        'magenta' => ['color'=>'magenta'],
        'cyan' => ['color'=>'cyan']
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
        
        return $this->color($text,$settings);
        
    }
    /**
     * Colors a string
     *
     * @param string $text  'some random text'
     * @param array $settings ['color'=>'blue','background'=>'red','blink'=>true]
     * @return void
     */
    public function color(string $text,array $settings){
      
        $ansi = [];
        if (isset($settings['color']) and isset($this->foregroundColors[$settings['color']])) {
            $ansi[] = $this->foregroundColors[$settings['color']];
        }
        if (isset($settings['background']) and isset($this->backgroundColors[$settings['background']])) {
            $ansi[] = $this->backgroundColors[$settings['background']];
        }
        unset($settings['color'], $settings['background']);
        foreach ($settings as $option => $value) {
            if ($value AND isset($this->options[$option])) {
                $ansi[] = $this->options[$option];
            }
        }
        if(empty($ansi)){
            return $text;
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
     * @param array $values array('color' => 'white','background'=>'blue','bold' => true) or false to delete
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

}
