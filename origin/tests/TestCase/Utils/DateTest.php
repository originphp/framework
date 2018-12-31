<?php
/**
 * OriginPHP Framework
 * Copyright 2018 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright     Copyright (c) Jamiel Sharief
 *
 * @link          https://www.originphp.com
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Test\Utils;

use Origin\Utils\Date;
use IntlDateFormatter;

class DateTest extends \PHPUnit\Framework\TestCase
{
    public function testConvertTimezone()
    {
        $result = Date::convertTimezone('2018-12-26 22:00:00', 'Europe/Madrid', 'UTC');
        $this->assertEquals('2018-12-26 21:00:00', $result);

        $result = Date::convertTimezone('2018-12-26 21:00:00', 'UTC', 'Europe/Madrid');
        $this->assertEquals('2018-12-26 22:00:00', $result);
    }

    public function testFormat()
    {
        // Test Locale Switching
        Date::setLocale('fr_FR');
        $this->assertEquals('27/12/2018 13:02', Date::format('2018-12-27 13:02:00'));

        Date::setLocale('ko_KR');
        $this->assertEquals('18. 12. 27. 오후 1:02', Date::format('2018-12-27 13:02:00'));

        // Test Timezone
        Date::setTimezone('America/Los_Angeles');
        Date::setLocale('en_US');
        $this->assertEquals('12/27/18, 5:02 AM', Date::format('2018-12-27 13:02:00'));

        Date::setTimezone('UTC');

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

        // Test Overide settings
        Date::setDateFormat([IntlDateFormatter::FULL, IntlDateFormatter::NONE]);
        $this->assertEquals('Thursday, December 27, 2018', Date::format('2018-12-27'));

        Date::setDateFormat('dd MMM y');
        $this->assertEquals('27 Dec 2018', Date::format('2018-12-27'));

        Date::setDatetimeFormat([IntlDateFormatter::MEDIUM, IntlDateFormatter::MEDIUM]);
        $this->assertEquals('Dec 27, 2018, 1:02:00 PM', Date::format('2018-12-27 13:02:00'));

        Date::setDatetimeFormat('dd MMM y H:mm');
        $this->assertEquals('27 Dec 2018 13:02', Date::format('2018-12-27 13:02:00'));

        Date::setTimeFormat([IntlDateFormatter::NONE, IntlDateFormatter::MEDIUM]);
        $this->assertEquals('1:02:00 PM', Date::format('13:02:00'));

        Date::setTimeFormat('H:mm');
        $this->assertEquals('13:02', Date::format('13:02:00'));

        Date::setDateFormat([IntlDateFormatter::SHORT, IntlDateFormatter::NONE]); // Reset
        Date::setDatetimeFormat([IntlDateFormatter::SHORT, IntlDateFormatter::SHORT]); // Reset
        Date::setTimeFormat([IntlDateFormatter::NONE, IntlDateFormatter::SHORT]); // Reset
    }

    public function testParse()
    {
        Date::setLocale('en_US');
        Date::setTimezone('America/Los_Angeles');

        $this->assertEquals('2018-12-27 21:02:00', Date::parse('12/27/18, 1:02 PM'));

        Date::setTimezone('UTC');
        $this->assertEquals(
            '2018-12-27 15:00:00',
            Date::parse('27 Dec, 2018 15:00', 'dd MMM, y H:mm')
        );

        $this->assertEquals(
            '2018-12-27',
            Date::parse('12/27/2018', [IntlDateFormatter::SHORT, IntlDateFormatter::NONE])
            );
    }

    public function testToServer()
    {
        Date::setTimezone('Europe/Madrid'); // + 1 hour
        $this->assertEquals('2018-12-27 10:56:00', Date::toServer('2018-12-27 11:56:00'));
    }
}
