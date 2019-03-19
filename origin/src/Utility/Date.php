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
  * Date Utility - This is non Intl version
  */

namespace Origin\Utility;

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
        'date' => 'm/d/Y',
        'datetime' => 'm/d/Y H:i',
        'time' => 'H:i'
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
     * @return array|null
     */
    public static function locale(array $locale = null)
    {
        if ($locale === null) {
            return static::$locale;
        }
        $locale += [
            'timezone' => 'UTC',
            'date' => 'm/d/Y',
            'datetime' => 'm/d/Y H:i',
            'time' => 'H:i'
        ];
        static::$locale = $locale;
    }

    /**
     * Formats a MySQL date to the user date format either by autodetection or a specific format
     *
     * @param string|null $value
     * @param string|null $type date,datetime,time
     * @return void
     */
    public static function format(string $dateString, string $format = null)
    {
        if ($format) {
            if (static::$locale['timezone'] != 'UTC') {
                $dateString = static::convertTimezone($dateString, 'UTC', static::$locale['timezone']);
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
     * Formats a MySQL datestring into user date string (timezone conversion happens if timezone
     * set to anything other than UTC)
     *
     * @param string $dateString
     * @return string
     */
    public static function formatDate(string $dateString)
    {
        if (static::$locale['timezone'] != 'UTC') {
            $dateString = static::convertTimezone($dateString, 'UTC', static::$locale['timezone']);
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
    public static function formatDateTime(string $dateString)
    {
        if (static::$locale['timezone'] != 'UTC') {
            $dateString = static::convertTimezone($dateString, 'UTC', static::$locale['timezone']);
        }
        return date(static::$locale['datetime'], strtotime($dateString));
    }

    /**
     * Formats a MySQL datestring into user time string (timezone conversion happens if timezone
     * set to anything other than UTC)
     *
     * @param string $dateString
     * @return string
     */
    public static function formatTime(string $dateString)
    {
        if (strpos($dateString, ' ') === false) {
            $dateString = "2019-01-01 {$dateString}"; // Add fictious date to work
        }
        if (static::$locale['timezone'] != 'UTC') {
            $dateString = static::convertTimezone($dateString, 'UTC', static::$locale['timezone']);
        }
        return date(static::$locale['time'], strtotime($dateString));
    }

    /**
     * Parses a date string
     *
     * @param string $dateString
     * @return void
     */
    public static function parseDate(string $dateString)
    {
        return static::convertFormat($dateString, static::$locale['date'], 'Y-m-d');
    }

    /**
     * Parses a time string
     *
     * @param string $dateString
     * @return void
     */
    public static function parseDateTime(string $dateString)
    {
        $dateString = static::convertFormat($dateString, static::$locale['datetime'], 'Y-m-d H:i:s');
        if ($dateString and static::$locale['timezone'] != 'UTC') {
            $dateString = static::convertTimezone($dateString, static::$locale['timezone'], 'UTC');
        }
        return $dateString;
    }

    /**
     * Parses a time string and converts to a MySQL time string. PHP script
     * should always be in UTC else you can expect undesirable results. If you really
     * must store in local, then dont adjust timezone, if its a UTC then no conversions happen
     *
     * @param string $timeString
     * @return void
     */
    public static function parseTime(string $timeString)
    {
        $timeString = static::convertFormat($timeString, static::$locale['time'], 'H:i:s');
       
        if ($timeString and static::$locale['timezone'] != 'UTC') { // date_default_timezone_get()
            if (strpos($timeString, ' ') === false) {
                $timeString = "2019-01-01 {$timeString}"; // Add fictious date to work
            }
            $timeString = static::convertTimezone($timeString, static::$locale['timezone'], 'UTC');
            $timeString = date('H:i:s', strtotime($timeString)); // Remove date from string
        }
        return $timeString;
    }

    /**
     * Converts a format of a valid strtotime() string.
     * @example Date::convertFormat('25/02/2019', 'd/m/Y', 'Y-m-d'); // 2019-02-25
     * @param string $datetime
     * @param string $fromFormat
     * @param string $toFormat
     */
    public static function convertFormat(string $datetime, string $fromFormat, string $toFormat)
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
     *
     * @return string Y-m-d H:i:s (new timezone)
     */
    public static function convertTimezone(string $datetime, string $fromTimezone, string $toTimezone)
    {
        $date = DateTime::createFromFormat('Y-m-d H:i:s', $datetime, new DateTimeZone($fromTimezone));
        if ($date) {
            $date->setTimeZone(new DateTimeZone($toTimezone));

            return $date->format('Y-m-d H:i:s');
        }

        return null;
    }
}
