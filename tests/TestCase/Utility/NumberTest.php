<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2021 Jamiel Sharief.
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
use InvalidArgumentException;

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

        $this->assertEquals('1,234,567.00', Number::format('1234567', ['places' => 2]));

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

    public function testReadableSize()
    {
        $this->assertEquals('0 Bytes', Number::readableSize(0));
        $this->assertEquals('123 Bytes', Number::readableSize(123));
        $this->assertEquals('1.21 KB', Number::readableSize(1234));
        $this->assertEquals('12.06 KB', Number::readableSize(12345));
        $this->assertEquals('1.18 MB', Number::readableSize(1234567));
        $this->assertEquals('1.15 GB', Number::readableSize(1234567890));
        $this->assertEquals('1.12 TB', Number::readableSize(1234567890123));
        $this->assertEquals('1.10 PB', Number::readableSize(1234567890123456));
        $this->assertEquals('1.07 EB', Number::readableSize(1234567890123456789));
        
        $this->assertEquals('1.18 MB', Number::readableSize(1234567, ['precision' => 2]));
        $this->assertEquals('1.122833 TB', Number::readableSize(1234567890123, ['precision' => 6]));
    }

    public function testParseSize()
    {
        $this->assertEquals(0, Number::parseSize('0 Bytes'));
        $this->assertEquals(123, Number::parseSize('123 Bytes'));
        $this->assertEquals(1024, Number::parseSize('1 KB'));
        $this->assertEquals(1048576, Number::parseSize('1 MB'));
        $this->assertEquals(1073741824, Number::parseSize('1 GB'));
        $this->assertEquals(1099511627776, Number::parseSize('1 TB'));
        $this->assertEquals(1125899906842624, Number::parseSize('1 PB'));
        $this->assertEquals(1152921504606846976, Number::parseSize('1 EB'));

        $this->assertEquals(1048576, Number::parseSize('1MB')); // no space
        $this->assertEquals(1048576, Number::parseSize('1mb')); // lowercase

        $this->assertEquals(1048576 * 1.5, Number::parseSize('1.5 MB'));
        $this->assertEquals(1048576 * 1.25, Number::parseSize('1.25 MB'));
        
        $this->assertEquals(1048576 * 1.25, Number::parseSize('1.25mb'));

        $this->expectException(InvalidArgumentException::class);
        Number::parseSize('10 Kilometers');
    }
}
