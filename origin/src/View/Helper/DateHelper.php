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
     * @return void
     */
    public function format($dateString, string $format = null)
    {
        if ($dateString) {
            return Date::format($dateString, $format);
        }
        return null;
    }
}
