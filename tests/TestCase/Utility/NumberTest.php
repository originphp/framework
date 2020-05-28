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

use Origin\Utility\Number;

class NumberTest extends \PHPUnit\Framework\TestCase
{
    public function testFormat()
    {
        $this->assertEquals('1,234,567', Number::format('1234567')); // test integer
        $this->assertEquals('1,234,567', Number::format(1234567)); // test integer
        $this->assertEquals('1,234,567.00', Number::format((float) 1234567)); // test float
        $this->assertEquals('1,234,567.00', Number::format(1234567.00)); // test float
        $this->assertEquals('1,234,567.00', Number::format('1234567.00')); // test float
        $this->assertEquals('1,234,567.80', Number::format(1234567.801234));

        $backup = Number::locale();
        $this->assertEquals('1,200.23', Number::format(1200.2345));

        Number::locale(['currency' => 'USD', 'thousands' => '.','decimals' => ',','places' => 3]);
        $this->assertEquals('1.200,235', Number::format(1200.2345));
        Number::locale($backup);
    }
    public function testPrecision()
    {
        $this->assertEquals('100.00', Number::precision(100));
        $this->assertEquals('123,456,789.12', Number::precision(123456789.12345678910));
        $this->assertEquals('123,456,789.1231', Number::precision(123456789.123123123, 4));
    }
    public function testPercentage()
    {
        $this->assertEquals('99.00%', Number::percent(99));
        $this->assertEquals('50.00%', Number::percent(.50, 2, ['multiply' => true]));
    }
    public function testCurrency()
    {
        $this->assertEquals('$1,234.57', Number::currency(1234.56789));
        $this->assertEquals('£1,234.57', Number::currency(1234.56789, 'GBP'));
        $this->assertEquals('DIR 1,234.57', Number::currency(1234.56789, 'DIR'));
        Number::addCurrency('NOK');
        $this->assertEquals('NOK 1,234.57', Number::currency(1234.56789, 'NOK'));
    }
    public function testParse()
    {
        $this->assertEquals(1234, Number::parse('1,234'));
        $this->assertEquals(1234.789, Number::parse('1,234.789'));
        $this->assertEquals(1234.789, Number::parse('1.234,789', ['thousands' => '.','decimals' => ',']));

        $this->assertNull(Number::parse('abc'));
    }

    public function testFormatNegative()
    {
        $this->assertEquals('(1,234,567)', Number::format('-1234567')); // test integer
        $this->assertEquals('(1,234,567)', Number::format(-1234567)); // test integer
        $this->assertEquals('-1,234,567', Number::format('-1234567', ['negative' => 'not brackets'])); // test integer

        $this->assertEquals('($1,234.57)', Number::currency(-1234.56789, 'USD'));
        $this->assertEquals('-$1,234.57', Number::currency(-1234.56789, 'USD', ['negative' => 'not brackets']));
    }
}
