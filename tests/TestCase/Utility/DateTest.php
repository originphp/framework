<?php
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

namespace Origin\Test\Utility;

use Origin\Utility\Date;

class DateTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        Date::locale([
            'timezone' => 'UTC',
            'date' => 'm/d/Y',
            'datetime' => 'm/d/Y H:i:s',
            'time' => 'H:i:s',
        ]);
    }

    public function testGettersAndSetters()
    {
        Date::dateFormat('d/m/Y');
        $this->assertEquals(Date::dateFormat(), 'd/m/Y');
        Date::datetimeFormat('d/m/Y H:i');
        $this->assertEquals(Date::datetimeFormat(), 'd/m/Y H:i');
        Date::timeFormat('H:i a');
        $this->assertEquals(Date::timeFormat(), 'H:i a');
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
    
    public function testFormatArgs()
    {
        Date::locale(['date' => 'd/m/Y','datetime' => 'd/m/Y g:i A']);
        $this->assertEquals('19/03/2019 11:54 AM', Date::format('2019-03-19 11:54:00'));
        $this->assertEquals('19/03/2019', Date::format('2019-03-19 11:54:00', 'd/m/Y'));

        Date::locale(['date' => 'd/m/Y','datetime' => 'd/m/Y g:i A','timezone' => 'Europe/Madrid']);
        $this->assertEquals('19-03-2019 12:54 PM', Date::format('2019-03-19 11:54:00', 'd-m-Y g:i A'));
        $this->assertEquals('19/03/2019', Date::format('2019-03-19 11:54:00', 'd/m/Y'));
    }

    public function testFormatFormat()
    {
        // Test No TZ
        Date::locale(['date' => 'd/m/Y','datetime' => 'd/m/Y g:i A','time' => 'g:i A']);
        $this->assertEquals('11-06-2019 4:00 PM', Date::format('2019-06-11 16:00:00', 'd-m-Y g:i A'));

        Date::locale(['date' => 'd/m/Y','datetime' => 'd/m/Y g:i A','time' => 'g:i A','timezone' => 'Europe/Madrid']);
        $this->assertEquals('11-06-2019 6:00 PM', Date::format('2019-06-11 16:00:00', 'd-m-Y g:i A'));
    }

    public function testFormatAutoDetect()
    {
        Date::locale(['date' => 'd/m/Y','datetime' => 'd/m/Y g:i A','time' => 'g:i A']);
        $this->assertEquals('11/06/2019 4:00 PM', Date::format('2019-06-11 16:00:00'));
        $this->assertEquals('11/06/2019', Date::format('2019-06-11'));
        $this->assertEquals('4:00 PM', Date::format('16:00:00'));

        $this->assertNull(Date::format('Invalid Date Format'));
    }

    public function testFormatDate()
    {
        Date::locale(['date' => 'd/m/Y','datetime' => 'd/m/Y g:i A']);
        $this->assertEquals('19/03/2019', Date::formatDate('2019-03-19'));

        Date::locale(['date' => 'd/m/Y','datetime' => 'd/m/Y g:i A','timezone' => 'Europe/Madrid']);
        $this->assertEquals('19/03/2019', Date::formatDate('2019-03-19'));
    }

    public function testFormatDateTime()
    {
        Date::locale(['datetime' => 'd/m/Y g:i A']);
        $this->assertEquals('19/03/2019 11:54 AM', Date::formatDateTime('2019-03-19 11:54:00'));

        Date::locale(['datetime' => 'd/m/Y g:i A','timezone' => 'Europe/Madrid']);
        $this->assertEquals('19/03/2019 12:54 PM', Date::formatDateTime('2019-03-19 11:54:00'));
    }

    public function testFormatTime()
    {
        Date::locale(['time' => 'g:i A']);
        $this->assertEquals('11:54 AM', Date::formatTime('11:54:00'));

        Date::locale(['time' => 'g:i A','timezone' => 'Europe/Madrid']);
        $this->assertEquals('11:54 AM', Date::formatTime('11:54:00'));
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
        $this->assertEquals('19/03/2019', Date::formatDate('2019-03-19')); // Still need to run through timezone
    }
}
