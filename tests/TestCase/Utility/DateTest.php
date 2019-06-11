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

namespace Origin\Test\Utility;

use Origin\Utility\Date;

class DateTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        Date::locale([
            'timezone' => 'UTC',
            'date' => 'm/d/Y',
            'datetime' => 'm/d/Y H:i:s',
            'time' => 'H:i:s'
        ]);
    }

    public function testConvertTimezone()
    {
        $result = Date::convertTimezone('2018-12-26 22:00:00', 'Europe/Madrid', 'UTC');
        $this->assertEquals('2018-12-26 21:00:00', $result);

        $result = Date::convertTimezone('2018-12-26 21:00:00', 'UTC', 'Europe/Madrid');
        $this->assertEquals('2018-12-26 22:00:00', $result);
        $this->assertNull(Date::convertTimezone('foo', 'UTC', 'Europe/Madrid'));
    }


    public function testConvertFormat()
    {
        $this->assertEquals('2019-02-25', Date::convertFormat('25/02/2019', 'd/m/Y', 'Y-m-d'));
        $this->assertNull(Date::convertFormat('foo', 'd/m/Y', 'Y-m-d'));
    }
    
    public function testFormatDate()
    {
        Date::locale(['date' => 'd/m/Y']);
        $this->assertEquals('19/03/2019', Date::format('2019-03-19'));
    }

    public function testFormatDateTime()
    {
        Date::locale(['datetime' => 'd/m/Y g:i A']);
        $this->assertEquals('19/03/2019 11:54 AM', Date::format('2019-03-19 11:54:00'));
        Date::locale(['datetime' => 'd/m/Y g:i A','timezone' => 'Europe/Madrid']);
        $this->assertEquals('19/03/2019 12:54 PM', Date::format('2019-03-19 11:54:00'));
    }

    public function testFormatTime()
    {
        Date::locale(['time' => 'g:i A']);
        $this->assertEquals('11:54 AM', Date::format('11:54:00'));
        Date::locale(['time' => 'g:i A','timezone' => 'Europe/Madrid']);
        $this->assertEquals('11:54 AM', Date::format('11:54:00'));

        Date::locale(['time' => 'g:i A','timezone' => 'Europe/Madrid']);
        $this->assertEquals('12:54 PM', Date::formatTime('2019-01-01 11:54:00'));
    }

    public function testParseDate()
    {
        Date::locale(['date' => 'd/m/Y']);
        $this->assertEquals('2019-03-19', Date::parseDate('19/03/2019'));
    }
    public function testParseDateTime()
    {
        Date::locale(['datetime' => 'd/m/Y H:i:s']);
        $this->assertEquals('2019-03-19 12:00:00', Date::parseDateTime('19/03/2019 12:00:00'));
        Date::locale(['datetime' => 'd/m/Y H:i:s','timezone' => 'Europe/Madrid']); // always 1 hour out
        $this->assertEquals('2019-03-19 11:00:00', Date::parseDateTime('19/03/2019 12:00:00'));
    }
    public function testParseTime()
    {
        Date::locale(['time' => 'g:i A']);
        $this->assertEquals('12:00:00', Date::parseTime('12:00 PM'));

        // No Timezone conversion for times anymore
        Date::locale(['time' => 'g:i A','timezone' => 'Europe/Madrid']); 
        $this->assertEquals('12:00:00', Date::parseTime('12:00 PM'));
    }

    public function testLocale()
    {
        $this->assertIsArray(Date::locale());
    }

    public function testFormatDateConversion()
    {
        Date::locale(['date' => 'd/m/Y','timezone' => 'Europe/Madrid']);
        $this->assertEquals('19/03/2019', Date::formatDate('2019-03-19 10:00:00')); // Still need to run through timezone
    }
}
