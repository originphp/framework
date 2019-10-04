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

namespace Origin\Utility;

class Inflector
{
    /**
     * Inflector User Dictonary.
     *
     * @var array
     */
    protected static $dictonary = [];

    /**
     * Holds caching from functions.
     *
     * @var array
     */
    protected static $cache = [];

    /**
     * Core set of rules for inflection that work pretty well.
     *
     * @var array
     */
    protected static $rules = [
        'plural' => [
            '/([^aeiouy]|qu)y$/i' => '\1ies',
            '/(ch|sh|ss|us|x)$/i' => '\1es',
            '/^$/' => '',
        ],

        'singular' => [
            '/([^aeiouy]|qu)ies$/i' => '\1y',
            '/(ch|sh|ss|us|x)es$/i' => '\1',
            '/^$/' => '',
            '/s$/i' => '',
        ],
    ];

    /**
     * Converts a word to purual form.
     *
     * @param string $singular apple,orange,banana
     * @return string $plural
     */
    public static function plural(string $singular) : string
    {
        if (isset(self::$dictonary[$singular])) {
            return self::$dictonary[$singular];
        }

        if (isset(self::$cache['pluralize'][$singular])) {
            return self::$cache['pluralize'][$singular];
        }

        foreach (self::$rules['plural'] as $pattern => $replacement) {
            if (preg_match($pattern, $singular)) {
                self::$cache['pluralize'][$singular] = preg_replace($pattern, $replacement, $singular);

                return self::$cache['pluralize'][$singular];
            }
        }

        return $singular . 's';
    }

    /**
     * Converts a word to singular form.
     *
     * @param string $plural apples,oranges,bananas
     * @return string $singular
     */
    public static function singular(string $plural) : string
    {
        if ($key = array_search($plural, self::$dictonary)) {
            return $key;
        }

        if (isset(self::$cache['singularize'][$plural])) {
            return self::$cache['singularize'][$plural];
        }

        foreach (self::$rules['singular'] as $pattern => $replacement) {
            if (preg_match($pattern, $plural)) {
                self::$cache['singularize'][$plural] = preg_replace($pattern, $replacement, $plural);

                return self::$cache['singularize'][$plural];
            }
        }

        return $plural;
    }

    /**
     * Converts an underscored word to mixed camelCase.
     *
     * @param string $underscoredWord studly_caps
     * @return string lowerCamelCase
     */
    public static function studlyCaps(string $underscoredWord) : string
    {
        if (isset(self::$cache['studlyCaps'][$underscoredWord])) {
            return self::$cache['studlyCaps'][$underscoredWord];
        }

        self::$cache['studlyCaps'][$underscoredWord] = str_replace(' ', '', ucwords(str_replace('_', ' ', $underscoredWord)));

        return self::$cache['studlyCaps'][$underscoredWord];
    }

    /**
     * Converts an underscored word to camelCase.
     *
     * @param string $underscoredWord camel_case
     * @return string CamelCase
     */
    public static function camelCase(string $underscoredWord) : string
    {
        if (isset(self::$cache['camelCase'][$underscoredWord])) {
            return self::$cache['camelCase'][$underscoredWord];
        }

        self::$cache['camelCase'][$underscoredWord] = lcfirst(self::studlyCaps($underscoredWord));

        return self::$cache['camelCase'][$underscoredWord];
    }

    /**
     * Undersores a StudlyCased word.
     *
     * @param string $studlyCasedWord StudlyCasedWord e.g. UserEmail
     * @return string $underscored_word
     */
    public static function underscored(string $studlyCasedWord) : string
    {
        if (isset(self::$cache['underscore'][$studlyCasedWord])) {
            return self::$cache['underscore'][$studlyCasedWord];
        }

        self::$cache['underscore'][$studlyCasedWord] = strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $studlyCasedWord));

        return self::$cache['underscore'][$studlyCasedWord];
    }

    /**
     * Takes a studly cased word word and underscores it, then converts to plural. Used for getting the table name
     * from a model name.
     *
     * @param string $studlyCasedWord
     * @return string $underscored
     */
    public static function tableName(string $studlyCasedWord) : string
    {
        if (isset(self::$cache['tableName'][$studlyCasedWord])) {
            return self::$cache['tableName'][$studlyCasedWord];
        }
        self::$cache['tableName'][$studlyCasedWord] = self::plural(self::underscored($studlyCasedWord));

        return self::$cache['tableName'][$studlyCasedWord];
    }

    /**
     * Converts a table name into a class name. E.g. user_emails -> UserEmail
     *
     * @param string $table contact_actitvities
     * @return string $className ContactActivities
     */
    public static function className(string $table) : string
    {
        if (isset(self::$cache['className'][$table])) {
            return self::$cache['className'][$table];
        }
        self::$cache['className'][$table] = self::studlyCaps(Inflector::singular($table));

        return self::$cache['className'][$table];
    }

    /**
     * Changes a underscored word into human readable. contact_manager -> Contact Manager
     *
     * @param string $underscoredWord contact_manager
     * @return string $result Contact Manger
     */
    public static function human(string $underscoredWord) : string
    {
        if (isset(self::$cache['human'][$underscoredWord])) {
            return self::$cache['human'][$underscoredWord];
        }
        self::$cache['human'][$underscoredWord] = ucwords(str_replace('_', ' ', $underscoredWord));

        return self::$cache['human'][$underscoredWord];
    }

    /**
     * Add user defined rules for the inflector.
     *
     * Inflector::rules('singular',['/(quiz)zes$/i' => '\\1']);
     * Inflector::rules('plural',['/(quiz)$/i' => '\1zes']);
     *
     * @param string $type  singular or plural
     * @param array  $rules [regexFindPattern => regexReplacementPattern] e.g ['/(quiz)$/i' => '\1zes']
     * @return void
     */
    public static function rules(string $type, array $rules) : void
    {
        foreach ($rules as $find => $replace) {
            static::$rules[$type] = [$find => $replace] + static::$rules[$type];
        }
    }

    /**
     * Add to user defined dictonary for inflector.
     *
     * Inflector::add('cactus', 'cacti');
     *
     * @param string $singular underscored person happy_person
     * @param string $plural   underscored people happy_people
     * @return void
     */
    public static function add(string $singular, string $plural) : void
    {
        self::$dictonary[$singular] = $plural;
        self::$dictonary[self::studlyCaps($singular)] = self::studlyCaps($plural);
    }
}
