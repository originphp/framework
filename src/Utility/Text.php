<?php
declare(strict_types = 1);
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
 * @see https://api.rubyonrails.org/classes/ActionView/Helpers/TextHelper.html
 */
namespace Origin\Utility;

use Origin\Exception\Exception;

class Text
{
    /**
     * Generates a random string. It relies on random_int which uses random_bytes.
     *
     * @param integer $length
     * @return string
     */
    public static function random(int $length = 16): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'; // 62 chars
        $out = '';
        for ($i = 0; $i < $length; ++$i) {
            $out .= $characters[random_int(0, 61)]; // 61 is count of chars - 1
        }

        return $out;
    }

    /**
    * Checks if a string contains a substring
    *
    * @param string $needle
    * @param string $haystack
    * @return bool
    */
    public static function contains(string $needle, string $haystack) : bool
    {
        return $needle !== '' and (mb_strpos($haystack, $needle) !== false);
    }

    /**
     * Gets part of the string from the left part of characters
     *
     * @param string $characters   :
     * @param string $string    key:value
     * @return string|null      key
     */
    public static function left(string $characters, string $string) : ?string
    {
        if (! empty($characters)) {
            $position = mb_strpos($string, $characters);
            if ($position === false) {
                return null;
            }
    
            return mb_substr($string, 0, $position);
        }

        return null;
    }

    /**
     * Gets part of the string from the right part of characters
     *
     * @param string $characters   :
     * @param string $string    key:value
     * @return string|null     value
     */
    public static function right(string $characters, string $string) : ?string
    {
        if (! empty($characters)) {
            $position = mb_strpos($string, $characters);
            if ($position === false) {
                return null;
            }
    
            return mb_substr($string, $position + mb_strlen($characters));
        }

        return null;
    }

    /**
     * Checks if a string starts with another string
     *
     * @param string $needle
     * @param string $haystack
     * @return boolean
     */
    public static function startsWith(string $needle, string $haystack) : bool
    {
        $length = mb_strlen($needle);

        return  ($needle !== '' and mb_substr($haystack, 0, $length) === $needle);
    }

    /**
     * Checks if a string ends with another string
     *
     * @param string $needle
     * @param string $haystack
     * @return boolean
     */
    public static function endsWith(string $needle, string $haystack) : bool
    {
        $length = mb_strlen($needle);

        return ($needle !== '' and mb_substr($haystack, -$length, $length) === $needle);
    }

    /**
     * Inserts values into a string by replacing placeholders. (string interpolation)
     *
     * @param string $string
     * @param array $values
     * @param array $options (before,after) e.g. ['before'=>':', 'after'=>'']
     * @return string
     */
    public static function insert(string $string, array $values = [], array $options = []) : string
    {
        $options += ['before' => '{','after' => '}'];
        $replace = [];
        foreach ($values as $key => $value) {
            $key = "{$options['before']}{$key}{$options['after']}";
            $replace[$key] = $value;
        }

        return strtr($string, $replace);
    }

    /**
     * Replaces text in strings.
     *
     * @internal str_replace works with multibyte string see https://php.net/manual/en/ref.mbstring.php#109937
     * @param mixed $needle
     * @param mixed $with
     * @param mixed $haystack
     * @param array $options (insensitive = false)
     *  - insensitive: default false. case-insensitive replace
     * @return string
     */
    public static function replace($needle, $with, $haystack, array $options = []) : string
    {
        $options += ['insensitive' => false];
        if ($options['insensitive']) {
            return str_ireplace($needle, $with, $haystack);
        }

        return str_replace($needle, $with, $haystack);
    }

    /**
     * Returns the length of a string
     *
     * @param string $string
     * @return integer
     */
    public static function length(string $string = null) : int
    {
        return mb_strlen($string);
    }

    /**
     * Converts a string to lower case
     *
     * @param string $string
     * @return string
     */
    public static function lower(string $string) :string
    {
        return mb_strtolower($string);
    }

    /**
     * Converts a stirng to uppercase
     *
     * @param string $string
     * @return string
     */
    public static function upper(string $string) :string
    {
        return mb_strtoupper($string);
    }

    /**
     * Alias for transliterate
     *
     * @param string $string
     * @param string $transliterator
     * @return string
     */
    public static function toAscii(string $string, string $transliterator = null) : string
    {
        return static::transliterate($string, $transliterator);
    }

    /**
     * Converts all characters in a string to ASCII
     *
     * @param string $string
     * @param string $transliterator
     * @return string
     * @see https://www.php.net/manual/en/transliterator.transliterate.php
     */
    public static function transliterate(string $string, string $transliterator = null) : string
    {
        if ($transliterator === null) {
            $transliterator = 'Any-Latin; Latin-ASCII; [\u0080-\u7fff] remove';
        }

        return transliterator_transliterate($transliterator, $string);
    }

    /**
     * Parses a string into an array
     *
     * @param string $string
     * @param array $options
     *  - keys: an array of names to be mapped as key names
     *  - separator: default:space
     *  - enclosure: default:"
     *  - escape: default:\ one character only

     * @return void
     */
    public static function tokenize(string $string, array $options = []) : array
    {
        $options += ['keys' => [],'separator' => ' ','enclosure' => '"','escape' => '\\'];
        $result = str_getcsv($string, $options['separator'], $options['enclosure'], $options['escape']);
        if (empty($options['keys'])) {
            return $result;
        }
        $out = [];
        if (count($options['keys']) !== count($result)) {
            throw new Exception('Invalid amount of keys');
        }
        foreach ($options['keys'] as $i => $key) {
            $out[$key] = $result[$i];
        }

        return $out;
    }

    /**
     * Creates a slug
     *
     * @param string $string
     * @return string
     */
    public static function slug(string $string) : string
    {
        $ascii = static::transliterate($string);
        $ascii = str_replace(' ', '-', mb_strtolower($ascii));
        $ascii = preg_replace('/[^a-z0-9-]/i', '', $ascii);

        return $ascii;
    }

    /**
     * Truncates a string
     *
     * @param string $string
     * @param array $options (length:30, end:...)
     * @return void
     */
    public static function truncate(string $string, array $options = [])
    {
        $options += ['length' => 30,'end' => '...'];
        if (mb_strwidth($string) <= $options['length']) {
            return $string;
        }
        $string = mb_strimwidth($string, 0, $options['length'], '');

        return rtrim($string) . $options['end'];
    }

    /**
     * Wraps a string to a given number of characters
     *
     * @param string $string
     * @param array $options The options are
     *  - width: default:80
     *  - break: default:\n what to break lines with
     *  - cut: default:false to cut string
     * @return string
     */
    public static function wordWrap(string $string, array $options = [])
    {
        $options += ['width' => 80,'break' => "\n",'cut' => false];

        return wordwrap($string, $options['width'], $options['break'], $options['cut']);
    }
}
