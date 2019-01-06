<?php
/**
 * OriginPHP Framework
 * Copyright 2018 Jamiel Sharief.
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


    protected $foregroundColors = array(
        'black' => 30,
        'red' => 31,
        'green' => 32,
        'yellow' => 33,
        'blue' => 34,
        'magenta' => 35,
        'cyan' => 36,
        'white' => 37
    );

    protected $backgroundColors = array(
        'black' => 40,
        'red' => 41,
        'green' => 42,
        'yellow' => 43,
        'blue' => 44,
        'magenta' => 45,
        'cyan' => 46,
        'white' => 47
    );

    protected $options = array(
        'bold' => 1,
        'underline' => 4,
        'blink' => 5,
        'reverse' => 7,
    );

    protected $styles = array(
      'primary' => array('text' => 'white','background'=>'blue','bold' => true),
      'success' => array('text' =>'white', 'background' => 'green','bold' => true),
      'danger' => array('text' => 'white','background'=>'red','bold' => true),
      'info' => array('text' => 'white','background' => 'cyan','bold' => true),
      'warning' => array('text' => 'white','background' => 'yellow','bold' => true),
      'prompt' => array('text' => 'cyan'),
      'green' => array('text' => 'green'),
      'blue' => array('text' => 'blue'),
      'yellow' => array('text' => 'yellow'),
      'red' => array('text' => 'red'),
      'white' => array('text' => 'white')
    );


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
    * Parses tags in text with ansi stuff
    * @param  string $string text
    * @return string        text
    */
    protected function parseTags($string)
    {
        if (preg_match_all('/<([a-z0-9-_]+)>(.*?)<\/([a-z0-9-_]+)>/', $string, $matches)) {
            foreach ($matches[1] as $key => $tag) {
                $text = $matches[2][$key];

                $string = str_replace("<{$tag}>{$text}</{$tag}>", $this->style($tag, $text), $string);
            }
        }
        
        return $string;
    }

    /**
     * Generates the styled string
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
     
        return "\033[" . implode($ansi, ';') . 'm' . $text . "\033[0;37m";
    }

    /**
     * Add a new style
     *
     * @param string $name
     * @param array $values text,background,bold,underline,blink
     * @return void
     */
    public function styles(string $name, array $values = [])
    {
        $this->styles[$name] = $values;
    }
}
