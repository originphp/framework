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

namespace Origin\I18n;

use DateTime;
use DateTimeZone;
use IntlDateFormatter;

/**
 * @todo change setters/getters to
 */
class Date
{
    protected static $locale = 'en_US';

    protected static $timezone = 'UTC';

    /**
     * Holds the default date format used by Date::format().
     *
     * @var string
     */
    protected static $dateFormat = [IntlDateFormatter::SHORT, IntlDateFormatter::NONE];

    /**
     * Holds the default datetime format used by Date::format().
     *
     * @var string
     */
    protected static $datetimeFormat = [IntlDateFormatter::SHORT, IntlDateFormatter::SHORT];

    /**
     * Holds the default time format used by Date::format().
     *
     * @var string
     */
    protected static $timeFormat = [IntlDateFormatter::NONE, IntlDateFormatter::SHORT];

   /**
    * Sets or gets the locale
    *
    * @param string $locale
    * @return string|void
    */
    public static function locale(string $locale = null)
    {
        if($locale === null){
            return self::$locale;
        }
        self::$locale = $locale;
    }

    /**
     * Sets or gets the timezone
     *
     * @param string $timezone
     * @return string|void
     */
    public static function timezone(string $timezone=null)
    {
        if($timezone === null){
           return self::$timezone;
        }
        self::$timezone = $timezone;
    }

    /**
     * Gets or sets the dateformat using pattern of intl settings.
     *
     * @param string|array $dateFormat 'dd MMM' or [IntlDateFormatter::SHORT, IntlDateFormatter::NONE]
     */
    public static function dateformat($dateFormat = null)
    {
        if($dateFormat === null){
            return self::$dateFormat;
        }
        self::$dateFormat = $dateFormat;
    }

    /**
     * Gets or sets the datetimeformat using pattern of intl settings. Possible patterns can be found at http://userguide.icu-project.org/formatparse/datetime.
     *
     * @param string|array $datetimeFormat 'dd MMM, y H:mm' or [IntlDateFormatter::SHORT, IntlDateFormatter::SHORT]
     */
    public static function datetimeFormat($datetimeFormat=null)
    {
        if($datetimeFormat === null){
            return self::$datetimeFormat;
        }
        self::$datetimeFormat = $datetimeFormat;
    }

    /**
     * Gets or sets the datetimeformat using pattern of intl settings.
     *
     * @param string|array $timeFormat 'H:mm' or [IntlDateFormatter::NONE, IntlDateFormatter::SHORT]
     */
    public static function timeFormat($timeFormat = null)
    {
        if($timeFormat === null){
            return self::$timeFormat;
        }
        self::$timeFormat = $timeFormat;
    }

    // # # # i18n # # #

    /**
     * Formats a strtotime() valid string to local time and translates it.
     *
     * @internal careful of DST with time formatting
     * @param string            $dateString
     * @param null|array|string $format     we will autodetect
     * @return string
     */
    public static function format($dateString, $format = null)
    {
        if ($format === null) {
            if (preg_match('/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', $dateString)) {
                $format = self::$datetimeFormat;
            } elseif (preg_match('/(\d{4})-(\d{2})-(\d{2})/', $dateString)) {
                $format = self::$dateFormat;
            } elseif (preg_match('/(\d{2}):(\d{2}):(\d{2})/', $dateString)) {
                $format = self::$timeFormat;
            } else {
                return null;
            }
        }

        $formatter = self::formatter($format);

        return $formatter->format(new DateTime($dateString));
    }

    /**
     * Formats a date using date defaults.
     *
     * @param string $dateString
     */
    public static function formatDate(string $dateString, $format = null)
    {
        if ($format === null) {
            $format = self::$dateFormat;
        }
        return self::format($dateString, $format);
    }

    /**
     * Formats a datetime using date defaults.
     *
     * @param string $dateString
     */
    public static function formatDatetime(string $dateString, $format = null)
    {
        if ($format === null) {
            $format = self::$datetimeFormat;
        }
        return self::format($dateString, $format);
    }

    /**
     * Formats a datetime using date defaults.
     *
     * @param string $dateString
     */
    public static function formatTime(string $dateString, $format = null)
    {
        if ($format === null) {
            $format = self::$timeFormat;
        }
        return self::format($dateString, $format);
    }

    /**
     * Parses a i18n datetime string and returns a strtotime() valid string in local time
     * Default format is [IntlDateFormatter::SHORT,IntlDateFormatter::SHORT].
     *
     *  $date = Date::parse('12/27/18, 1:02 PM');
     *  $date = Date::parse('27 Dec, 2018 15:00', 'dd MMM, y H:mm');
     *  $date = Date::parse('12/27/2018', [IntlDateFormatter::SHORT, IntlDateFormatter::NONE]));.
     *
     * @param string            $dateString
     * @param null|string|array $format
     * @return null|dateString strtotime() valid string
     */
    public static function parse(string $dateString, $format = null)
    {
        $formatter = self::formatter($format);
        /**
         * @link http://userguide.icu-project.org/formatparse/datetime
         */
        $pattern = $formatter->getPattern();
        $hasDate = (stripos($pattern, 'y') !== false);
        $hasTime = (stripos($pattern, 'h') !== false);
   
        $returnFormat = 'Y-m-d H:i:s';
        if ($hasTime and !$hasDate) {
            $returnFormat = 'H:i:s';
        } elseif ($hasDate and !$hasTime) {
            $returnFormat = 'Y-m-d';
        }

        $timestamp = $formatter->parse($dateString);
        if ($timestamp !== false) {
            return date($returnFormat, $timestamp);
        }

        return null;
    }

    /**
     * Parses a date string
     * Date::parseDate('27 Dec, 2018 15:00', 'dd MMM, y H:mm');
     * @param string $dateString
     * @param [type] $format
     * @return void
     */
    public static function parseDate(string $dateString, $format = null)
    {
        if ($format === null) {
            $format = self::$dateFormat;
        }
        $formatter = self::formatter($format);

        $timestamp = $formatter->parse($dateString);

        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }
        return null;
    }

    /**
     * @internal Careful of dst
     *
     * @param string $dateString
     * @param string $format
     * @return void
     */
    public static function parseTime(string $dateString, $format = null)
    {
        if ($format === null) {
            $format = self::$timeFormat;
        }
        $formatter = self::formatter($format);

        $timestamp = $formatter->parse($dateString);

        if ($timestamp !== false) {
            return date('H:i:s', $timestamp);
        }
        return null;
    }

    public static function parseDatetime(string $dateString, $format = null)
    {
        if ($format === null) {
            $format = self::$datetimeFormat;
        }
       
        $formatter = self::formatter($format);

        $timestamp = $formatter->parse($dateString);

        if ($timestamp !== false) {
            return date('Y-m-d H:i:s', $timestamp);
        }

        return null;
    }

    /**
     * Returns a configured IntlDateFormatter used by both i18nFormat and parse.
     *
     * @param null|string|array $format
     * @return \IntlDateFormatter
     */
    protected static function formatter($format = null)
    {
        $dateFormat = $timeFormat = $pattern = null;

        if (is_array($format)) {
            list($dateFormat, $timeFormat) = $format;
        } elseif (is_numeric($format)) {
            $dateFormat = $format;
            $timeFormat = IntlDateFormatter::NONE;
        } else {
            $dateFormat = $timeFormat = IntlDateFormatter::SHORT;
            $pattern = $format;
        }

        /**
         * The Following calendars use Traditional vs Greg
         *  Japanese,Buddhist,Chinese,Persian,Indian,Islamic,Hebrew,Coptic,Ethiopic.
         * Non Gregorian calendars need to be speced in locale e.g fa_IR@calendar=PERSIAN”.
         *
         * @link https://twig-extensions.readthedocs.io/en/latest/intl.html
         */
        $calendar = IntlDateFormatter::GREGORIAN;
        if (preg_match('/buddhist|chinese|coptic|ethiopic|hebrew|indian|islamic|japanese|/', self::$locale)) {
            $calendar = IntlDateFormatter::TRADITIONAL;
        }
        $formatter = new IntlDateFormatter(
            self::$locale,
            $dateFormat,
            $timeFormat,
            self::$timezone,
            $calendar,
            $pattern
        );

        return $formatter;
    }

    /*
26.12.18 - These are the Unique Date formats with intl
   [0] => y-MM-dd
    [3] => d/M/y
    [5] => yy/MM/dd
    [7] => dd/MM/y
    [9] => d‏/M‏/y
    [30] => d‏/M‏/y GGGGG
    [38] => d-M-y
    [42] => d/M/yy
    [44] => dd.MM.yy
    [51] => d.MM.yy
    [57] => d.MM.yy 'г'.
    [69] => M/d/yy
    [71] => d.M.yy.
    [92] => GGGGG y-MM-dd
    [95] => dd/MM/yy
    [112] => d.M.yy
    [197] => d/MM/yy
    [231] => y/MM/dd
    [234] => yy-MM-dd
    [241] => dd-MM-yy
    [255] => MM/dd/yy
    [266] => yy/M/d
    [270] => y/M/d
    [278] => d.M.y
    [362] => dd. MM. y.
    [363] => d. M. yy.
    [367] => y. MM. dd.
    [406] => dd/MM y
    [416] => yy. M. d.
    [419] => d-M-yy
    [427] => d. M. y
    [474] => dd.M.yy
    [478] => y.MM.dd
    [496] => dd.MM.y
    [521] => d/MM/y
    [540] => GGGGG y/M/d
    [604] => d. MM. yy
    [632] => dd-MM-y
    [658] => d.MM.y
*/
    /*
    Unique time patterns

    [0] => HH:mm
        [5] => h:mm a
        [18] => H:mm
        [38] => h.mm. a
        [57] => H:mm 'ч'.
        [97] => HH.mm
        [118] => ཆུ་ཚོད་ h སྐར་མ་ mm a
        [122] => a 'ga' h:mm
        [155] => H.mm
        [291] => HH 'h' mm
        [346] => hh:mm a
        [365] => H:mm 'hodź'.
        [416] => a h:mm
        [490] => B H:mm
        [703] => ah:mm
    */
}
