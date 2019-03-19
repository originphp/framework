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
  * Number Utility - This is non Intl version
  */

namespace Origin\Utility;

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
        ]
        ];

    protected static $locale = [
            'currency' => 'USD',
            'thousands' => ',',
            'decimals' => '.',
            'places' => 2,
          ];

    /**
     * Sets and gets the locale array
     *
     * @param array $locale
     * @return array|null
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
            'places' => 2
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
    public static function addCurrency(string $symbol, $options=[])
    {
        self::$currencies[$symbol] = $options + ['name'=>$symbol,'before'=>$symbol,'after'=>''];
    }

    /**
     * Formats a number with a level of precision
     *
     * @param float $value
     * @param integer $precision
     * @return string
     */
    public static function decimal($value, int $precision = 2)
    {
        return static::format($value, ['places'=>$precision]);
    }
    
    /**
     * Formats a number of a perctenage
     *
     * @param float $value
     * @param integer $precision
     * @param array $options
     * @return void
     */
    public static function percent($value, int $precision = 2, $options = [])
    {
        $options += ['multiply' => false];
        if ($options['multiply']) {
            $value *= 100;
        }
        return static::decimal($value, $precision) . '%';
    }
    /**
     * Formats a number
     *
     * @param string|float $value
     * @param array $options
     * @return string
     */
    public static function format($value, array $options=[])
    {
        $locale = static::$locale;
        $options += [
            'before' => '',
            'after'=>'',
            'places' => $locale['places'],
            'thousands' => $locale['thousands'],
            'decimals' => $locale['decimals']
        ];
        $formatted = number_format($value, $options['places'], $options['decimals'], $options['thousands']);
        return $options['before'] .   $formatted  . $options['after'];
    }

    public static function currency($value, string $currency = null, array $options=[])
    {
        if ($currency === null) {
            $currency = static::$locale['currency'];
        }
        if (isset(static::$currencies[$currency])) {
            $options += static::$currencies[$currency];
        } else {
            $options += ['before' =>  $currency . ' ', 'after'=>''];
        }
        return static::format($value, $options);
    }

    /**
     * Parses a localized number into MySQL
     *
     * @param string|float $value
     * @param array $options
     * @return string
     */
    public static function parse($value, array $options= [])
    {
        $options += [
            'thousands' => static::$locale['thousands'],
            'decimals' => static::$locale['decimals']
        ];
        $value = str_replace($options['thousands'], '', (string) $value);
        return str_replace($options['decimals'], '.', $value);
    }
}
