<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2021 Jamiel Sharief.
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
namespace Origin\Http\View\Helper;

use ResourceBundle;
use Origin\I18n\Date;
use Origin\I18n\I18n;
use Origin\I18n\Number;

class IntlHelper extends Helper
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
    public function date($dateString, $format = null): string
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
    public function time($dateString, $format = null): string
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
    public function datetime($dateString, $format = null): string
    {
        return Date::formatDateTime($dateString, $format);
    }

    /**
     * Formats a number into a currency.
     *
     * @param float  $value
     * @param string $currency EUR
     * @param array  $options  precision|places|before|after|pattern
     * @return string result $1,234.56
     */
    public function currency(float $value, string $currency = null, array $options = []): string
    {
        return Number::currency($value, $currency, $options);
    }
    /**
     * Formats a number. This is used by other functions.
     *
     * @param float $value
     * @param array $options precision|places|before|after|pattern
     * @return string 1,234.56
     */
    public function number(float $value, array $options = []): string
    {
        return Number::format($value, $options);
    }
    /**
     * Formats a floating point number.
     *
     * @param float $value
     * @param int   $precision max number of decimal places
     * @param array $options   places|before|after|pattern
     * @return string 1234.56
     */
    
    public function precision(float $value, int $precision = 2, array $options = []): string
    {
        return Number::precision($value, $precision, $options);
    }

    /**
     * Formats a number to a percentage.
     *
     * @param float $value
     * @param int   $precision number of decimal places
     * @param array $options   places|before|after|pattern|multiply
     * @return string 75.00%
     */
    public function percent(float $value, int $precision = 2, array $options = []): string
    {
        return Number::percent($value, $precision, $options);
    }

    /**
     * Returns a list of locale with display name in the current language
     * if language is not set. Use this for a picklist in your app for locale selection.
     *
     * @param string $language
     * @return array $locales [en_GB => English (United Kingdom)]
     */
    public function locales(string $language = null): array
    {
        $list = [];
        if ($language === null) {
            $language = I18n::language();
        }

        $locales = ResourceBundle::getLocales(''); //Returns a numerical array of locales supported by the PHP INT extension.
        foreach ($locales as $locale) {
            $list[$locale] = locale_get_display_name($locale, $language);
        }

        return $list;
    }

    /**
     * Returns a list of timezones in English.
     * @todo investigate IntlTimeZone::getDisplayName
     */
    public function timezones()
    {
        $list = [];
        $timestamp = time();
        $originalTimeZone = date_default_timezone_get();
        foreach (timezone_identifiers_list() as $key => $zone) {
            date_default_timezone_set($zone);
            $list[$zone] = 'GMT '.date('P', $timestamp).' - '. str_replace('_', ' ', $zone);
        }
        date_default_timezone_set($originalTimeZone);

        return $list;
    }
}
