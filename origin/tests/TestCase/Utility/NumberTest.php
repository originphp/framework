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

use Origin\Utility\Number;

class NumberTest extends \PHPUnit\Framework\TestCase
{
    public function testFormat()
    {
        $this->assertEquals('1,200.23', Number::format(1200.2345));
        $backup = Number::locale();
        Number::locale(['currency' => 'USD', 'thousands' => '.','decimals' => ',','places' => 3]);
        $this->assertEquals('1.200,235', Number::format(1200.2345));
        Number::locale($backup);
    }
    public function testPrecision()
    {
        $this->assertEquals('123,456,789.12', Number::decimal(123456789.12345678910));
        $this->assertEquals('123,456,789.1231', Number::decimal(123456789.123123123, 4));
    }
    public function testPercentage()
    {
        $this->assertEquals('99.00%', Number::percentage(99));
        $this->assertEquals('50.00%', Number::percentage(.50, 2, ['multiply'=>true]));
    }
    public function testCurrency()
    {
        $this->assertEquals('$1,234.57', Number::currency(1234.56789));
        $this->assertEquals('£1,234.57', Number::currency(1234.56789, 'GBP'));
        $this->assertEquals('DIR 1,234.57', Number::currency(1234.56789, 'DIR'));
    }
    public function testParse()
    {
        $this->assertEquals('1234.789', Number::parse('1,234.789'));
        $this->assertEquals('1234.789', Number::parse('1.234,789', ['thousands'=>'.','decimals'=>',']));
    }
}
