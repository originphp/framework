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

namespace Origin\Core;

/**
 * @deprecated This has been deprecated
 * @codeCoverageIgnore
 */
class Inflector
{
    /**
     * Inflector User Dictonary.
     *
     * @var array
     */
    private static $dictonary = [];

    /**
     * Holds caching from functions.
     *
     * @var array
     */
    private static $cache = [];

    /**
     * Core set of rules for inflection that work pretty well.
     *
     * @var array
     */
    private static $rules = [
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
    public static function pluralize(string $singular) : string
    {
        deprecationWarning('Origin\Core\Inflector has been deprecated use Origin\Utility\Inflector');

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
    public static function singularize(string $plural) : string
    {
        deprecationWarning('Origin\Core\Inflector has been deprecated use Origin\Utility\Inflector');

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
     * Camelize is really studly caps (aka Pascal Case)
     * varible is really camel cased
     */

    /**
     * Converts an underscored word to CamelCase.
     *
     * @param string $underscoredWord camel_case
     * @return string CamelCase
     */
    public static function camelize(string $underscoredWord) : string
    {
        deprecationWarning('Origin\Core\Inflector has been deprecated use Origin\Utility\Inflector');
        
        if (isset(self::$cache['camelize'][$underscoredWord])) {
            return self::$cache['camelize'][$underscoredWord];
        }

        self::$cache['camelize'][$underscoredWord] = str_replace(' ', '', ucwords(str_replace('_', ' ', $underscoredWord)));

        return self::$cache['camelize'][$underscoredWord];
    }

    /**
     * Converts an underscored word to mixed camelCase.
     *
     * @param string $underscoredWord camel_case
     * @return string lowerCamelCase
     */
    public static function variable(string $underscoredWord) : string
    {
        deprecationWarning('Origin\Core\Inflector has been deprecated use Origin\Utility\Inflector');

        if (isset(self::$cache['variable'][$underscoredWord])) {
            return self::$cache['variable'][$underscoredWord];
        }

        self::$cache['variable'][$underscoredWord] = lcfirst(self::studlyCaps($underscoredWord));

        return self::$cache['variable'][$underscoredWord];
    }

    /**
     * Undersores a CamelCased word.
     *
     * @param string $camelCasedWord
     * @return string $underscored_word
     */
    public static function underscore(string $camelCasedWord) : string
    {
        deprecationWarning('Origin\Core\Inflector has been deprecated use Origin\Utility\Inflector');

        if (isset(self::$cache['underscore'][$camelCasedWord])) {
            return self::$cache['underscore'][$camelCasedWord];
        }

        self::$cache['underscore'][$camelCasedWord] = strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $camelCasedWord));

        return self::$cache['underscore'][$camelCasedWord];
    }

    /**
     * Takes a CamelCased word and underscores it, then converts to plural. Used for getting the table name
     * from a model name.
     *
     * @param string $camelCase
     * @return string $underscored
     */
    public static function tableize(string $camelCasedWord) : string
    {
        deprecationWarning('Origin\Core\Inflector has been deprecated use Origin\Utility\Inflector');

        if (isset(self::$cache['tableize'][$camelCasedWord])) {
            return self::$cache['tableize'][$camelCasedWord];
        }
        self::$cache['tableize'][$camelCasedWord] = self::plural(self::underscored($camelCasedWord));

        return self::$cache['tableize'][$camelCasedWord];
    }

    /**
     * Converts a tablename into a class name.
     *
     * @param string $table contact_actitvities
     * @return string $className ContactActivities
     */
    public static function classify(string $table) : string
    {
        deprecationWarning('Origin\Core\Inflector has been deprecated use Origin\Utility\Inflector');

        if (isset(self::$cache['classify'][$table])) {
            return self::$cache['classify'][$table];
        }
        self::$cache['classify'][$table] = self::studlyCaps(Inflector::singular($table));

        return self::$cache['classify'][$table];
    }

    /**
     * Changes a underscored word into human readable.
     *
     * @param string $underscoredWord contact_manager
     *
     * @return string $result Contact Manger
     */
    public static function humanize(string $underscoredWord) : string
    {
        deprecationWarning('Origin\Core\Inflector has been deprecated use Origin\Utility\Inflector');

        if (isset(self::$cache['humanize'][$underscoredWord])) {
            return self::$cache['humanize'][$underscoredWord];
        }
        self::$cache['humanize'][$underscoredWord] = ucwords(str_replace('_', ' ', $underscoredWord));

        return self::$cache['humanize'][$underscoredWord];
    }

    /**
     * Add user defined rules for the inflector.
     *
     * Inflector::rules('singular',['/(quiz)zes$/i' => '\\1']);
     * Inflector::rules('plural',['/(quiz)$/i' => '\1zes']);
     *
     * @param string $type  singular or plural
     * @param array  $rules array(regexPattern => replacement)
     * @return void
     */
    public static function rules(string $type, array $rules) : void
    {
        deprecationWarning('Origin\Core\Inflector has been deprecated use Origin\Utility\Inflector');
        
        foreach ($rules as $find => $replace) {
            self::$rules[$type] = [$find => $replace] + self::$rules[$type];
        }
    }

    /**
     * Add user defined dictonary for inflector.
     *
     * Inflector::add('cactus', 'cacti');
     *
     * @param string $singular underscored person happy_person
     * @param string $plural   underscored people happy_people
     * @return void
     */
    public static function add(string $singular, string $plural) : void
    {
        deprecationWarning('Origin\Core\Inflector has been deprecated use Origin\Utility\Inflector');
        
        self::$dictonary[$singular] = $plural;
        self::$dictonary[self::studlyCaps($singular)] = self::studlyCaps($plural);
    }
}
