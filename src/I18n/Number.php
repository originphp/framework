<?php
declare(strict_types = 1);
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

namespace Origin\I18n;

use NumberFormatter;

class Number
{
    /**
     * Holds the default Locale use by the number formatter.
     *
     * @var string
     */
    protected static $locale = 'en_US';

    /**
     * Holds the default Currency which used by Number::currency if set.
     *
     * @var string
     */
    protected static $currency = 'USD';

    /**
     * Sets the locale to be used by this Number utility regardless of PHP locale setting.
     *
     * @param string $locale en_US en_GB etc
     */
    public static function locale(string $locale)
    {
        self::$locale = $locale;
    }

    /**
     * Sets/gets the default currency.
     *
     * @param string $currency EUR|USD|GBP
     * @return string $currency
     */
    public static function defaultCurrency(string $currency = null) : string
    {
        if ($currency === null) {
            return self::$currency;
        }

        return self::$currency = $currency;
    }

    /**
     * Formats a number into a currency.
     *
     * @param float  $value
     * @param string $currency EUR,
     * @param array  $options  precision|places|before|after|pattern
     * @return string|null result $1,234.56
     */
    public static function currency(float $value, string $currency = null, array $options = []) : ?string
    {
        if ($currency === null) {
            $currency = self::$currency;
        }

        return static::format($value, ['type' => NumberFormatter::CURRENCY, 'currency' => $currency] + $options);
    }

    /**
     * Formats a number to a percentage.
     *
     * @param float $value
     * @param int   $precision number of decimal places
     * @param array $options   places|before|after|pattern|multiply
     * @return string|null 75.00%
     */
    public static function percent(float $value, int $precision = 2, array $options = []) :? string
    {
        if (! empty($options['multiply'])) {
            $value = $value * 100;
        }

        return static::format($value, ['precision' => $precision] + $options).'%';
    }

    /**
     * Formats a floating point number.
     *
     * @param float $value
     * @param int   $precision number of decimal places
     * @param array $options   places|before|after|pattern
     * @return string|null 1234.56
     */
    public static function precision(float $value, int $precision = 2, array $options = []) : ?string
    {
        return static::format($value, ['precision' => $precision] + $options);
    }

    /**
     * Formats a number. This is used by other functions.
     *
     * @param float $value
     * @param array $options precision|places|before|after|pattern
     * @return string|null 1234.56
     */
    public static function format($value, array $options = []) : ?string
    {
        $options += [
            'type' => NumberFormatter::DECIMAL, 'before' => null, 'after' => null,
        ];
    
        if ($options['type'] === NumberFormatter::CURRENCY) {
            $formatted = static::formatter($options)->formatCurrency($value, $options['currency']);
        } else {
            $formatted = static::formatter($options)->format($value);
        }
        
        return $options['before'] . $formatted . $options['after'];
    }

    /**
     * Parses a localized string
     * Use case converting user input.
     *
     * @example 123,456,789.25 -> 123456789.25
     * @param string $string
     * @param mixed $type NumberFormatter::DECIMAL,NumberFormatter::CURRENCY,NumberFormatter::INT_32
     * @return int|double
     */

    public static function parse(string $string, $type = NumberFormatter::DECIMAL)
    {
        $formatter = new NumberFormatter(static::$locale, $type);

        return $formatter->parse($string);
    }

    /*
    Parse seems to work fine. Not as intented.
    public static function parseDecimal(string $string)
    {
        return static::parse($string, NumberFormatter::DECIMAL);
    }

    public static function parseInteger(string $string)
    {
        return static::parse($string, NumberFormatter::TYPE_INT32);
    }
    */

    /**
     * Creates a NumberFormatter object and sets the attributes.
     *
     * @param array $options Option keys are
     *   - locale
     *   - type
     *   - places
     *   - precision
     *   - pattern
     * @return NumberFormatter
     */
    protected static function formatter(array $options = []) : NumberFormatter
    {
        $locale = static::$locale;
        if (isset($options['locale'])) {
            $locale = $options['locale'];
        }
        $formatter = new NumberFormatter($locale, $options['type']);
        // Minimum decmial places
        if (isset($options['places'])) {
            $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $options['places']);
        }

        // Maximum decimal places
        if (isset($options['precision'])) {
            $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $options['precision']);
        }
        // http://php.net/manual/en/numberformatter.setpattern.php
        if (isset($options['pattern'])) {
            $formatter->setPattern($options['pattern']);
        }

        return $formatter;
    }
}
