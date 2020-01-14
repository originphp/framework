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

namespace Origin\Test\I18n;

use Origin\I18n\Date;
use IntlDateFormatter;

class DateTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        Date::locale('en_US');
    }
    protected function tearDown(): void
    {
        Date::locale('en_US');
    }

    public function testLocale()
    {
        $this->assertEquals('en_US', Date::locale());
        Date::locale('en_GB');
        $this->assertEquals('en_GB', Date::locale());
        Date::locale('en_US');
    }

    public function testTimezone()
    {
        $this->assertEquals('UTC', Date::timezone());
        Date::timezone('Europe/London');
        $this->assertEquals('Europe/London', Date::timezone());
        Date::timezone('UTC');
    }

    public function testFormat()
    {
        // Test Locale Switching
        Date::locale('fr_FR');
        $this->assertEquals('27/12/2018 13:02', Date::format('2018-12-27 13:02:00'));

        Date::locale('ko_KR');
        $this->assertEquals('18. 12. 27. 오후 1:02', Date::format('2018-12-27 13:02:00'));

        // Test Timezone
        Date::timezone('America/Los_Angeles');
        Date::locale('en_US');
        $this->assertEquals('12/27/18, 5:02 AM', Date::format('2018-12-27 13:02:00'));

        Date::timezone('UTC');

        // Test Formatting
        $this->assertEquals('12/27/18, 1:02 PM', Date::format('2018-12-27 13:02:00', null));
        $this->assertEquals('12/27/18', Date::format('2018-12-27', null));
        $this->assertEquals('1:02 PM', Date::format('13:02:00', null));

        // Test Options Params
        $options = [IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE];
        $this->assertEquals('Dec 27, 2018', Date::format('2018-12-27 13:02:00', $options));

        $options = [IntlDateFormatter::MEDIUM, IntlDateFormatter::MEDIUM];
        $this->assertEquals('Dec 27, 2018, 1:02:00 PM', Date::format('2018-12-27 13:02:00', $options));

        $options = [IntlDateFormatter::NONE, IntlDateFormatter::MEDIUM];
        $this->assertEquals('1:02:00 PM', Date::format('2018-12-27 13:02:00', $options));

        $this->assertEquals('Feb 25, 2019', Date::format('2019-02-25 08:20:00', IntlDateFormatter::MEDIUM));
        
        // Test Overide settings
        Date::dateFormat([IntlDateFormatter::FULL, IntlDateFormatter::NONE]);
        $this->assertEquals('Thursday, December 27, 2018', Date::format('2018-12-27'));

        Date::dateFormat('dd MMM y');
        $this->assertEquals('27 Dec 2018', Date::format('2018-12-27'));

        Date::datetimeFormat([IntlDateFormatter::MEDIUM, IntlDateFormatter::MEDIUM]);
        $this->assertEquals('Dec 27, 2018, 1:02:00 PM', Date::format('2018-12-27 13:02:00'));

        Date::datetimeFormat('dd MMM y H:mm');
        $this->assertEquals('27 Dec 2018 13:02', Date::format('2018-12-27 13:02:00'));

        Date::timeFormat([IntlDateFormatter::NONE, IntlDateFormatter::MEDIUM]);
        $this->assertEquals('1:02:00 PM', Date::format('13:02:00'));

        Date::timeFormat('H:mm');
        $this->assertEquals('13:02', Date::format('13:02:00'));

        Date::dateFormat([IntlDateFormatter::SHORT, IntlDateFormatter::NONE]); // Reset
        Date::datetimeFormat([IntlDateFormatter::SHORT, IntlDateFormatter::SHORT]); // Reset
        Date::timeFormat([IntlDateFormatter::NONE, IntlDateFormatter::SHORT]); // Reset

        $this->assertNull(Date::format('foo'));
    }

    public function testParse()
    {
        Date::locale('en_US');
        Date::timezone('America/Los_Angeles');

        $this->assertEquals('2018-12-27 21:02:00', Date::parse('12/27/18, 1:02 PM'));

        Date::locale('en_GB');
        Date::timezone('Europe/London');
        $this->assertEquals('2019-03-18 18:54:00', Date::parse('18/03/2019, 18:54 PM'));
        
        Date::locale('en_US');
        Date::timezone('UTC');
        
        $this->assertEquals(
            '2018-12-27 15:00:00',
            Date::parse('27 Dec, 2018 15:00', 'dd MMM, y H:mm')
        );

        $this->assertEquals(
            '2018-12-27',
            Date::parse('12/27/2018', [IntlDateFormatter::SHORT, IntlDateFormatter::NONE])
            );

        $this->assertEquals('07:50:00', Date::parse('7:50 AM', [IntlDateFormatter::NONE, IntlDateFormatter::SHORT]));
    
        $this->assertNull(Date::parse('foo'));
    }

    public function testParseDate()
    {
        $this->assertEquals('2019-02-25', Date::parseDate('02/25/2019'));
        $this->assertNull(Date::parseDate('foo'));
    }

    public function testParseDateTime()
    {
        $this->assertEquals('2019-02-25 07:50:00', Date::parseDateTime('02/25/2019, 7:50 AM'));
        $this->assertNull(Date::parseDateTime('foo'));
    }

    public function testParseTime()
    {
        $this->assertEquals('07:50:00', Date::parseTime('7:50 AM'));
        $this->assertNull(Date::parseTime('foo'));
    }

    public function testFormatDate()
    {
        $this->assertEquals('2/24/19', Date::formatDate('2019-02-24 21:00'));
        $this->assertEquals('2/24/19', Date::formatDate('2019-02-24'));
    }
    public function testFormatDateTime()
    {
        Date::locale('en_US');
        Date::timezone('Europe/Madrid');
        $this->assertEquals('2/24/19, 10:00 PM', Date::formatDateTime('2019-02-24 21:00'));
    }
    public function testFormatTime()
    {
        Date::timezone('Asia/Dubai'); // No daylight saving time
        $this->assertEquals('1:00 AM', Date::formatTime('2019-02-24 21:00'));
        $this->assertEquals('1:00 AM', Date::formatTime('21:00'));
        Date::timezone('UTC');
    }

    public function testSettersAndGetters()
    {
        Date::dateFormat('dd MMM');
        $this->assertEquals('dd MMM', Date::dateFormat());
        Date::datetimeFormat('dd MMM y H:mm');
        $this->assertEquals('dd MMM y H:mm', Date::datetimeFormat());
        Date::timeFormat('H:mm');
        $this->assertEquals('H:mm', Date::timeFormat());

        //dd MMM y H:mm
    }
}
