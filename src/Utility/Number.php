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
namespace Origin\Utility;

/**
  * Number Utility - This is non Intl version
  */

class Number
{
    protected static $currencies = [
        'AUD' => [
            'name' => 'Australian Dollar',
            'before' => '$',
            'after' => '',
        ],
        'CAD' => [
            'name' => 'Canadian Dollar',
            'before' => '$',
            'after' => '',
        ],
        'CHF' => [
            'name' => 'Swiss Franc',
            'before' => '',
            'after' => 'Fr',
        ],
        'EUR' => [
            'name' => 'Euro',
            'before' => '€',
            'after' => '',
        ],
        'GBP' => [
            'name' => 'British Pound',
            'before' => '£',
            'after' => '',
        ],
        'JPY' => [
            'name' => 'Japanese Yen',
            'before' => '¥',
            'after' => '',
        ],
        'USD' => [
            'name' => 'United States Dollar',
            'before' => '$',
            'after' => '',
        ],
    ];

    protected static $locale = [
        'currency' => 'USD',
        'thousands' => ',',
        'decimals' => '.',
        'places' => 2,
    ];

    /**
     * Sets and gets the locale array
     * @param array $locale accepts the following keys
     *  - currency: The ISO currency code e.g. USD, GBP
     *  - thousands: the thousands seperator
     *  - decimnals: the decimals seperator
     *  - places: the default number of places
     * @return array|void
     */
    public static function locale(array $locale = null)
    {
        if ($locale === null) {
            return static::$locale;
        }
        $locale += [
            'currency' => 'USD',
            'thousands' => ',',
            'decimals' => '.',
            'places' => 2,
        ];
        static::$locale = $locale;
    }

    /**
     * Adds a new currency to list
     * @todo add loading external files from config instead of this
     *
     * @param string $symbol
     * @param array $options
     * @return void
     */
    public static function addCurrency(string $symbol, $options = []): void
    {
        self::$currencies[$symbol] = $options + ['name' => $symbol,'before' => $symbol . ' ','after' => ''];
    }

    /**
     * Formats a number with a level of precision
     *
     * @param float $value
     * @param integer $precision
     * @return string
     */
    public static function precision($value, int $precision = 2): string
    {
        $value = sprintf("%01.{$precision}f", $value);

        return static::format($value, ['places' => $precision]);
    }
    
    /**
     * Formats a number of a perctenage
     *
     * @param string|float|integer $value
     * @param integer $precision
     * @param array $options multiply default is false
     * @return string
     */
    public static function percent($value, int $precision = 2, $options = []): string
    {
        $options += ['multiply' => false];
        if ($options['multiply']) {
            $value *= 100;
        }

        return static::precision($value, $precision) . '%';
    }
    /**
     * Formats a number from database.
     *
     * @param string|float|integer $value
     * @param array $options
     * @return string
     */
    public static function format($value, array $options = []) : string
    {
        $locale = static::$locale;

        $places = 0;
        if (is_float($value) or (is_string($value) and strpos($value, '.') !== false)) {
            $places = $locale['places'];
        }

        $options += [
            'before' => '',
            'after' => '',
            'places' => $places,
            'thousands' => $locale['thousands'],
            'decimals' => $locale['decimals'],
        ];

        $formatted = number_format((float) $value, $options['places'], $options['decimals'], $options['thousands']);
    
        return $options['before'] .  $formatted  . $options['after'];
    }

    /**
     * Formats a number to currency format
     *
     * @param string|float|integer $value
     * @param string $currency
     * @param array $options
     * @return string
     */
    public static function currency($value, string $currency = null, array $options = []) : string
    {
        if ($currency === null) {
            $currency = static::$locale['currency'];
        }
        
        // merge currency setitngs to options or create new one
        if (isset(static::$currencies[$currency])) {
            $options += static::$currencies[$currency];
        } else {
            $options += ['before' => $currency . ' ', 'after' => ''];
        }
       
        return static::format($value, $options);
    }

    /**
     * Parses a localized number into MySQL
     *
     * @param string|float|integer $value
     * @param array $options
     * @return mixed
     */
    public static function parse($value, array $options = [])
    {
        $options += [
            'thousands' => static::$locale['thousands'],
            'decimals' => static::$locale['decimals'],
        ];
        $value = str_replace($options['thousands'], '', (string) $value);
        $value = str_replace($options['decimals'], '.', $value);

        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float) $value : (int) $value;
        }
        return null;
    }
}
