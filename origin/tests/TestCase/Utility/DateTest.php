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
        Date::timezone('UTC');
        Date::dateFormat('m/d/Y');
        Date::dateTimeFormat('m/d/Y H:i:s');
        Date::timeFormat('H:i:s');
    }
    public function testFormatDate()
    {
        Date::dateFormat('d/m/Y');
        $this->assertEquals('19/03/2019', Date::format('2019-03-19'));
    }

    public function testFormatDateTime()
    {
        Date::dateTimeFormat('d/m/Y g:i A');
        $this->assertEquals('19/03/2019 11:54 AM', Date::format('2019-03-19 11:54:00'));
        Date::timezone('Europe/Madrid');  // always 1 hour out
        $this->assertEquals('19/03/2019 12:54 PM', Date::format('2019-03-19 11:54:00'));
    }

    public function testFormatTime()
    {
        Date::timeFormat('g:i A');
        $this->assertEquals('11:54 AM', Date::format('11:54:00'));
        Date::timezone('Europe/Madrid');  // always 1 hour out
        $this->assertEquals('12:54 PM', Date::format('11:54:00'));
    }

    public function testParseDate()
    {
        Date::dateFormat('d/m/Y');
        $this->assertEquals('2019-03-19', Date::parseDate('19/03/2019'));
    }
    public function testParseDateTime()
    {
        Date::dateTimeFormat('d/m/Y H:i:s');
        $this->assertEquals('2019-03-19 12:00:00', Date::parseDateTime('19/03/2019 12:00:00'));
        Date::timezone('Europe/Madrid');  // always 1 hour out
        $this->assertEquals('2019-03-19 11:00:00', Date::parseDateTime('19/03/2019 12:00:00'));
    }
    public function testParseTime()
    {
        Date::timeFormat('g:i A');
        $this->assertEquals('12:00:00', Date::parseTime('12:00 PM'));
        Date::timezone('Europe/Madrid');  // always 1 hour out
        $this->assertEquals('11:00:00', Date::parseTime('12:00 PM'));
    }
}
