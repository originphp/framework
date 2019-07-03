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
 * Helper for non Intl extension
 */
namespace Origin\View\Helper;

use Origin\Utility\Date;

class DateHelper extends Helper
{
    /**
     * Localizes a MySQL date,datetime,time string. If format is not specified it will
     * autodetect from the date string. Only format if you want to display time field using a datetime string or
     * you want to display the date in a different format from Date::
     *
     * @see http://php.net/manual/en/datetime.formats.date.php
     *
     * @param string|null $dateString
     * @param string $format date function compatiable string e.g 'H:i:s'
     * @return string
     */
    public function format(string $dateString = null, string $format = null)
    {
        if ($dateString) {
            return Date::format($dateString, $format);
        }
        return null;
    }

    /**
     * Takes a datetime string and formats in a friendly way. e.g. 3 minutes ago
     *
     * @param string $datetime
     * @return string
     */
    public function timeAgoInWords(string $datetime = null) : string
    {
        $time = strtotime($datetime);
        if ($time === false) {
            return null;
        }

        $now = time();
        $startTime = $time;
        $endTime = $now;

        $isFuture = false;
        if ($time > $now) {
            $isFuture = true;
            $startTime = $now;
            $endTime = $time;
        }

        $difference = $endTime - $startTime;

        if ($difference < 1) {
            return 'just now';
        }

        $conditions = [
            31104000 => 'year', //  12 * 30 * 24 * 60 * 60
            2592000 => 'month', //  30 * 24 * 60 * 60
            86400 => 'day', //  24 * 60 * 60
            3600 => 'hour', //  60 * 60
            60 => 'minute',
            1 => 'second',
        ];

        foreach ($conditions as $seconds => $type) {
            $result = $difference / $seconds;
            if ($result  >= 1) {
                $final = round($result);
                if ($final > 1) {
                    $type .= 's'; // inflect
                }
                return "{$final} {$type}" . ($isFuture === false ? ' ago' : '');
            }
        }
    }
}
