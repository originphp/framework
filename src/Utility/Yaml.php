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

/**
* This utility is for reading and writing YAML files, note. it is does not cover the complete specification.
* @see https://yaml.org/refcard.html
*
* The goal of this utility is to provide basic YAML functionality to read and write configuration files and data from the
* database which can be read or edited in a user friendly way.
*
* It supports:
* - scalar values
* - comments
* - lists (block sequences @see https://yaml.org/spec/current.html#id2543032)
* - dictonaries
* - dictonary lists
* - scalar blocks (plain, literal and folded)
*
* It currently does not support:
* - multiline quoted scalars example
* quoted: "So does this
* quoted scalar."
* However use description a string with \n or \r\n works fine (use literal | or folded >)
* - multiple documents in a stream @see https://yaml.org/spec/current.html#id2502724
* - Surround in-line series branch. e.g [ ]
* - Surround in-line keyed branch. { }
*
* Known issues: parsing a docker compose file, the volumes for mysql-data is the value.
* volumes:
* mysql-data:
* @see https://yaml.org/refcard.html
 */
namespace Origin\Utility;

use Origin\Utility\Exception\YamlException;

class YamlParser
{
    /**
     * The line endings
     */
    const EOF = "\r\n";

    /**
     * Copy of the source
     *
     * @var [type]
     */
    protected $src = null;

    /**
     * Array of the lines
     *
     * @var array
     */
    protected $lines = [];
    
    /**
     * Holds the line counter
     *
     * @var integer
     */
    protected $i = 0;

    /**
     * Constructor
     *
     * @param string $src Yaml source
     */
    public function __construct(string $src = null)
    {
        if ($src) {
            $this->src = $src;
            $this->lines = $this->readLines($src);
        }
    }

    /**
     * This is used to manually create lines e.g list of lists.
     *
     * @param array $lines
     * @return array
     */
    public function lines(array $lines = null) : array
    {
        if ($lines) {
            $this->lines = $lines;
        }

        return $this->lines;
    }

    /**
     * Help identify record sets
     *
     * @param int $from
     * @return array
     */
    protected function findRecordSets(int $from) : array
    {
        $lines = count($this->lines);
        $results = [];
        $spaces = strpos($this->lines[$from], ltrim($this->lines[$from]));
        
        $start = null;
        
        for ($w = $from;$w < $lines;$w++) {
            $marker = ltrim($this->lines[$w]);
            if ($marker[0] === '-') {
                if ($start !== null) {
                    $results[$start] = $w - 1;
                }
                $start = $w;
                if ($marker !== '-') {
                    $marker = substr($marker, 1);
                }
            }
            if (strpos($this->lines[$w], $marker) < $spaces) {
                $results[$start] = $w - 1;
                $start = null;
                break; // its parent
            } elseif ($w === ($lines - 1) and $start) {
                $results[$start] = $w; // Reached end of of file
            }
        }

        return $results;
    }

    /**
     * Parses the array
     *
     * @param integer $lineNo from
     * @return array
     */
    protected function parse(int $lineNo = 0) : array
    {
        $result = [];
        $lines = count($this->lines);
     
        $spaces = $lastSpaces = 0;
    
        for ($i = $lineNo;$i < $lines;$i++) {
            $line = $this->lines[$i];
            $marker = trim($line);
       
            // Skip comments,empty lines  and directive
            if ($marker === '' or $marker[0] === '#' or $line === '---' or substr($line, 0, '5') === '%YAML') {
                $this->i = $i;
                continue;
            }

            if ($line[0] === "\t") {
                throw new YamlException('YAML documents should not use tabs for indentation');
            }
            if ($line === '...') {
                throw new YamlException('Multiple document streams are not supported.');
            }
            
            // Identify node level
            $spaces = strpos($line, $marker);
            if ($spaces > $lastSpaces) {
                $lastSpaces = $spaces;
            } elseif ($spaces < $lastSpaces) {
                break;
            }

            // Walk forward for multi line data
            if (! $this->isList($line) and ! $this->isScalar($line) and ! $this->isParent($line)) {
                $parentLine = $this->lines[$i - 1];
                if (! $this->isParent($parentLine)) {
                    continue; // Skip if there is no parent
                }
                $block = trim($line);
                for ($w = $i + 1;$w < $lines;$w++) {
                    $nextLine = trim($this->lines[$w]);
                    if (! $this->isList($nextLine) and ! $this->isScalar($nextLine) and ! $this->isParent($nextLine)) {
                        $block .= ' ' . $nextLine; // In the plain scalar,newlines become spaces
                    } else {
                        break;
                    }
                }
                $this->i = $i = $w - 1;
                
                $result['__plain_scalar__'] = $block;
                continue;
            }
            // Walk Forward to handle multiline data folded and literal
            if (substr($line, -1) === '|' or substr($line, -1) === '>') {
                list($key, $value) = explode(': ', ltrim($line));
                $value = '';
                /**
                 * > Folded style: line breaks replaced with space
                 * | literal style: line breaks count
                 * @see https://yaml.org/spec/current.html#id2539942
                 */
                $break = "\n";
                if (substr($line, -1) === '>') {
                    $break = ' ';
                }
 
                for ($w = $i + 1;$w < $lines;$w++) {
                    $nextLine = trim($this->lines[$w]);

                    // Handle multilines which are on the last lastline
                    if ($w === $lines - 1) {
                        $value .= $nextLine . $break;
                    }
                    
                    if ($this->isScalar($nextLine) or $this->isParent($nextLine) or $this->isList($nextLine) or $w === $lines - 1) {
                        $result[$key] = rtrim($value);
                        break;
                    }
                    $value .= $nextLine . $break;
                }
                $this->i = $i = $w - 1;
                continue;
            }
            // Handle Lists
            if ($this->isList($line)) {
                $trimmedLine = ltrim(substr(ltrim($line), 2)); // work with any number of spaces;
               
                if (trim($line) !== '-' and ! $this->isParent($trimmedLine) and ! $this->isScalar($trimmedLine)) {
                    $result[] = $trimmedLine;
                } elseif ($this->isParent($trimmedLine)) {
                    $key = substr(ltrim($trimmedLine), 0, -1);
                    $result[$key] = $this->parse($i + 1);
                    $i = $this->i;
                } else {
                    /**
                     * Deal with list sets. Going to seperate from the rest. remove
                     * the - from the start each set and pass through the parser (is this a hack?)
                     */
                    $sets = $this->findRecordSets($i);
                    foreach ($sets as $start => $finish) {
                        $setLines = [];
                        for ($ii = $start;$ii < $finish + 1;$ii++) {
                            $setLine = $this->lines[$ii];
                       
                            if ($ii === $start) {
                                if (trim($setLine) === '-') {
                                    continue;
                                } else {
                                    $setLine = str_replace('- ', '  ', $setLine); // Associate
                                }
                            }
                            $setLines[] = $setLine;
                        }
                 
                        $me = new YamlParser();
                        $me->lines($setLines);
                        $result[] = $me->toArray();
                    }
                    $i = $finish;
                }
            } elseif ($this->isScalar($line)) {
                list($key, $value) = explode(': ', ltrim($line));
                $result[rtrim($key)] = $this->readValue($value);
            } elseif ($this->isParent($line)) {
                $line = ltrim($line);
                $key = substr($line, 0, -1);
            
                $key = rtrim($key);   // remove ending spaces e.g. invoice   :
                $result[$key] = $this->parse($i + 1);
                // Walk backward
                if (isset($result[$key]['__plain_scalar__'])) {
                    $result[$key] = $result[$key]['__plain_scalar__'];
                }
                
                $i = $this->i;
            }
            $this->i = $i;
        }
     
        return $result;
    }

    /**
     * Converts a string into an array of lines
     *
     * @param string $string
     * @return array
     */
    protected function readLines(string $string) : array
    {
        $lines = [];
        $lines[] = $line = strtok($string, static::EOF);
        while ($line !== false) {
            $line = strtok(static::EOF);
            if ($line) {
                $lines[] = $line;
            }
        }

        return $lines;
    }

    /**
     * Checks if a line is parent
     *
     * @param string $line
     * @return boolean
     */
    protected function isParent(string $line): bool
    {
        return (substr(trim($line), -1) === ':');
    }

    /**
     * Checks if a line is scalar value
     *
     * @param string $line
     * @return boolean
     */
    protected function isScalar(string $line) :bool
    {
        return (strpos($line, ': ') !== false);
    }

    /**
     * Checks if line is a list
     *
     * @param string $line
     * @return boolean
     */
    protected function isList(string $line) : bool
    {
        $line = trim($line);

        return (substr($line, 0, 2) === '- ') or $line === '-';
    }

    /**
     * Converts the string into an array
     *
     * @return void
     */
    public function toArray() : array
    {
        return $this->parse();
    }

    /**
     * Undocumented function
     * Many types of bool
     * @see https://yaml.org/type/bool.html
     * @param mixed $value
     * @return mixed
     */
    protected function readValue($value)
    {
        switch ($value) {
            case 'true':
                return true;
            break;
            case 'false':
                return false;
            break;
            case 'null':
                return null;
            break;
        }
       
        return trim($value, '"\''); // remove quotes spaces etc
    }
}

class Yaml
{
    const EOF = "\r\n";

    protected static $indent = 2;
    protected static $lines = [];

    /**
     * Converts a YAML string into an Array
     *
     * @param string $string
     * @return array
     */
    public static function toArray(string $string) : array
    {
        $parser = new YamlParser($string);

        return $parser->toArray();
    }
  
    /**
     * Converts an array into a YAML string
     *
     * @param array $array
     * @return string
     */
    public static function fromArray(array $array) : string
    {
        return self::dump($array);
    }

    protected static function dump(array $array, int $indent = 0, $isList = false)
    {
        $output = '';
        $line = 0;
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                if (is_int($key)) {
                    $output .= self::dump($value, $indent, true);
                } else {
                    $output .= str_repeat(' ', $indent)  . "{$key}: \n";
                    $output .= self::dump($value, $indent + self::$indent);
                }
            } else {
                $value = self::dumpValue($value);
                if (is_int($key)) {
                    $string = "- {$value}";
                } else {
                    $string = "{$key}: {$value}";
                }
                if ($isList and $line == 0) {
                    $string = '- ' . $string;
                }
                $output .= str_repeat(' ', $indent) . "{$string}\n";
                if ($isList and $line == 0) {
                    $indent = $indent + 2;
                }
            }
            $line ++;
        }

        return $output;
    }
    
    protected static function dumpValue($value)
    {
        if (is_bool($value)) {
            return $value?'true':'false';
        }
        if (is_null($value)) {
            return null;
        }
        if (strpos($value, "\n") !== false) {
            $value = "| {$value}";
        }

        return $value;
    }
}
