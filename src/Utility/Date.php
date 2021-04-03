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
namespace Origin\Utility;

/**
  * Date Utility - This is non Intl version
  */

use DateTime;
use DateTimeZone;

class Date
{
    /**
     * Holds the locale config for date formating and parsing
     *
     * @var array
     */
    protected static $locale = [
        'timezone' => 'UTC',
        'date' => 'Y-m-d',
        'datetime' => 'Y-m-d H:i',
        'time' => 'H:i',
    ];

    /**
     * Sets and gets the locale array
     *
     * Date::locale([
     *      'timezone' => 'UTC',
     *      'date' => 'm/d/Y',
      *     'datetime' => 'm/d/Y H:i',
     *      'time' => 'H:i'
      *    ]);
     *
     * @param array $locale
     * @return array
     */
    public static function locale(array $locale = null): array
    {
        if ($locale === null) {
            return static::$locale;
        }
        $locale += [
            'timezone' => 'UTC',
            'date' => 'Y-m-d',
            'datetime' => 'Y-m-d H:i',
            'time' => 'H:i',
        ];

        return static::$locale = $locale;
    }

    /**
     * Checks if timezone conversion is needed
     *
     * @return bool
     */
    protected static function convert(): bool
    {
        return (date_default_timezone_get() !== static::$locale['timezone']);
    }

    /**
     * Sets and gets the dateformat
     *
     * @param string $format
     * @return string
     */
    public static function dateFormat(string $format = null): string
    {
        if ($format === null) {
            return static::$locale['date'];
        }

        return static::$locale['date'] = $format;
    }

    /**
     * Sets and gets the datetimeformat
     *
     * @param string $format
     * @return string
     */
    public static function datetimeFormat(string $format = null): string
    {
        if ($format === null) {
            return static::$locale['datetime'];
        }

        return static::$locale['datetime'] = $format;
    }

    /**
     * Sets and gets the dateformat
     *
     * @param string $format
     * @return string
     */
    public static function timeFormat(string $format = null): string
    {
        if ($format === null) {
            return static::$locale['time'];
        }

        return static::$locale['time'] = $format;
    }

    /**
     * Formats a MySQL date to the user date format either by autodetection or a specific format
     *
     * @param string $dateString
     * @param string|null $format
     * @return string|null
     */
    public static function format(string $dateString, string $format = null): ?string
    {
        if ($format) {
            if (static::convert()) {
                if (preg_match('/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', $dateString)) {
                    $dateString = static::convertTimezone($dateString, date_default_timezone_get(), static::$locale['timezone']);
                } elseif (preg_match('/(\d{4})-(\d{2})-(\d{2})/', $dateString)) {
                    $dateString = static::convertTimezone($dateString .' 00:00:00', date_default_timezone_get(), static::$locale['timezone']);
                }
            }

            return date($format, strtotime($dateString));
        }
        if (preg_match('/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', $dateString)) {
            return static::formatDateTime($dateString);
        } elseif (preg_match('/(\d{4})-(\d{2})-(\d{2})/', $dateString)) {
            return static::formatDate($dateString);
        } elseif (preg_match('/(\d{2}):(\d{2}):(\d{2})/', $dateString)) {
            return static::formatTime($dateString);
        }

        return null;
    }

    /**
     * Formats a MySQL datestring into user date string and converts timezone if the timezone is different
     * to server timezone
     *
     * @param string $dateString
     * @return string
     */
    public static function formatDate(string $dateString): string
    {
        if (strpos($dateString, ':') === false) {
            $dateString .= ' 00:00:00';
        }
        if (static::convert()) {
            $dateString = static::convertTimezone($dateString, date_default_timezone_get(), static::$locale['timezone']);
        }

        return date(static::$locale['date'], strtotime($dateString));
    }

    /**
     * Formats a MySQL datestring into user datetime string (timezone conversion happens if timezone
     * set to anything other than UTC)
     *
     * @param string $dateString
     * @return string
     */
    public static function formatDateTime(string $dateString): string
    {
        if (static::convert()) {
            $dateString = static::convertTimezone($dateString, date_default_timezone_get(), static::$locale['timezone']);
        }

        return date(static::$locale['datetime'], strtotime($dateString));
    }

    /**
     * Formats a MySQL datestring into user time string. Timestrings are not timezone converted unless a date
     * is supplied. This is because there is no way of knowing if the date is in Daylight Saving
     * @param string $dateString
     * @return string
     */
    public static function formatTime(string $dateString): string
    {
        $hasDate = (strpos($dateString, ' ') !== false);

        // Add fictious date to work (careful of DST)
        if (! $hasDate) {
            $dateString = "2019-01-01 {$dateString}";
        }
        // Only convert timezone if date is supplied.
        if ($hasDate && static::convert()) {
            $dateString = static::convertTimezone($dateString, date_default_timezone_get(), static::$locale['timezone']);
        }

        return date(static::$locale['time'], strtotime($dateString));
    }

    /**
     * Parses a date string
     *
     * @param string $dateString
     * @return string|null
     */
    public static function parseDate(string $dateString): ?string
    {
        return static::convertFormat($dateString, static::$locale['date'], 'Y-m-d');
    }

    /**
     * Parses a time string
     *
     * @param string $dateString
     * @return string|null
     */
    public static function parseDateTime(string $dateString): ?string
    {
        $dateString = static::convertFormat($dateString, static::$locale['datetime'], 'Y-m-d H:i:s');
        if ($dateString && static::convert()) {
            $dateString = static::convertTimezone($dateString, static::$locale['timezone'], date_default_timezone_get());
        }

        return $dateString;
    }

    /**
     * Parses a time string and converts to a MySQL time string.
     * Timezone for times are not converted because without date its impossbile to know DST
     *
     * @param string $timeString
     * @return string|null
     */
    public static function parseTime(string $timeString): ?string
    {
        $timeString = static::convertFormat($timeString, static::$locale['time'], 'H:i:s');
    
        if ($timeString) {
            $timeString = date('H:i:s', strtotime("2019-01-01 {$timeString}"));
        }

        return $timeString;
    }

    /**
     * Converts a format of a valid strtotime() string.
     * @example Date::convertFormat('25/02/2019', 'd/m/Y', 'Y-m-d'); // 2019-02-25
     * @param string $datetime
     * @param string $fromFormat
     * @param string $toFormat
     * @return string|null
     */
    public static function convertFormat(string $datetime, string $fromFormat, string $toFormat): ?string
    {
        $date = DateTime::createFromFormat($fromFormat, $datetime);
        if ($date) {
            return $date->format($toFormat);
        }

        return null;
    }

    /**
     * Converts a datetime string to another timezone.
     *
     * @example Date::convertTimezone('2018-12-26 22:00:00', 'Europe/Madrid', 'UTC');
     *
     * @param string $datetime     Y-m-d H:i:s
     * @param string $fromTimezone
     * @param string $toTimezone
     * @return string|null Y-m-d H:i:s (new timezone)
     */
    public static function convertTimezone(string $datetime, string $fromTimezone, string $toTimezone): ?string
    {
        $date = DateTime::createFromFormat('Y-m-d H:i:s', $datetime, new DateTimeZone($fromTimezone));
        if ($date) {
            $date->setTimeZone(new DateTimeZone($toTimezone));

            return $date->format('Y-m-d H:i:s');
        }

        return null;
    }
}
