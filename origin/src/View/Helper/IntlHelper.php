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

namespace Origin\View\Helper;

use Origin\I18n\Date;
use Origin\I18n\Number;

class Intl extends Helper
{
    /**
     * Returns a localized date string according to locale settings
     * $intl->date('2019-01-01 08:52:00', 'dd MMM');
     * $intl->date('2019-01-01 08:52:00', [IntlDateFormatter::SHORT, IntlDateFormatter::NONE]));.
     *
     * @param string|null $dateString Y-m-d H:i:s
     * @param string|array $format pattern, IntlDateFormatter options
     * @return string
     */
    public function date($dateString, $format = null)
    {
        return Date::formatDate($dateString, $format);
    }
    /**
     * Returns a localized time string according to locale settings
     *
     * $intl->time('2019-01-01 08:52:00', 'H:mm');
     * $intl->time('2019-01-01 08:52:00', [IntlDateFormatter::NONE, IntlDateFormatter::LONG]));.

     * @param string|null $dateString Y-m-d H:i:s
     * @param string|array $format pattern, IntlDateFormatter options
     * @return string
     */
    public function time($dateString, $format = null)
    {
        return Date::formatTime($dateString, $format);
    }
    /**
    * Returns a localized datetime string according to locale settings
    * $intl->datetime('2019-01-01 08:52:00', 'dd MMM, y H:mm');
    * $intl->datetime('2019-01-01 08:52:00', [IntlDateFormatter::SHORT, IntlDateFormatter::SHORT]));.
    *
    * @param string|null $dateString Y-m-d H:i:s
    * @param string|array $format pattern, IntlDateFormatter options
    * @return string
    */
    public function datetime($dateString, $format = null)
    {
        return Date::formatDateTime($dateString, $format);
    }

    /**
     * Formats a number into a currency.
     *
     * @param float  $value
     * @param string $currency EUR
     * @param array  $options  precision|places|before|after|pattern
     *
     * @return string result $1,234.56
     */
    public function currency(float $value, string $currency = null, array $options = [])
    {
        return Number::currency($value, $currency, $options);
    }
    /**
     * Formats a number. This is used by other functions.
     *
     * @param float $value
     * @param array $options precision|places|before|after|pattern
     *
     * @return string 1,234.56
     */
    public function format(float $value, array $options = [])
    {
        return Number::format($value, $options);
    }
    /**
     * Formats a floating point number.
     *
     * @param float $value
     * @param int   $precision max number of decimal places
     * @param array $options   places|before|after|pattern
     *
     * @return string 1234.56
     */
    
    public function decimal(float $value, int $precision = 2, array $options = [])
    {
        return Number::decimal($value, $precision, $options);
    }

    /**
     * Formats a number to a percentage.
     *
     * @param float $value
     * @param int   $precision number of decimal places
     * @param array $options   places|before|after|pattern|multiply
     *
     * @return string 75.00%
     */
    public function percent(float $value, int $precision = 2, array $options = [])
    {
        return Number::percent($value, $precision, $options);
    }
}
