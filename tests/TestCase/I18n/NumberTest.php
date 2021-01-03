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

namespace Origin\Test\I18n;

use Origin\I18n\Number;

class NumberTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        Number::locale('en_US');
    }
    protected function tearDown(): void
    {
        Number::locale('en_US');
    }
    public function testCurrency()
    {
        $this->assertEquals('$1,234.56', Number::currency(1234.56));
        $this->assertEquals('$1,234.56', Number::currency('1234.56'));
        $this->assertEquals('$1,234.00', Number::currency(1234));
        $this->assertEquals('$1,234.56', Number::currency(1234.56, 'USD'));
        $this->assertEquals('£1,234.56', Number::currency(1234.56, 'GBP'));
        $this->assertEquals('€1,234.56', Number::currency(1234.56, 'EUR'));
        $options = ['before' => '(', 'after' => ')'];
        $this->assertEquals('($1,234.56)', Number::currency(1234.56, 'USD', $options));

        $options = ['places' => 2];
        $this->assertEquals('$1,234.00', Number::currency(1234, 'USD', $options));

        $options = ['precision' => 2];
        $this->assertEquals('$1,234.57', Number::currency(1234.56789, 'USD', $options));
    }

    /**
     * @depends testCurrency
     */
    public function testDefaultCurrency()
    {
        $this->assertEquals('USD', Number::defaultCurrency());
        Number::defaultCurrency('GBP');
        $this->assertEquals('GBP', Number::defaultCurrency());
        $this->assertEquals('£1,234.56', Number::currency(1234.56));
    }

    public function testPrecision()
    {
        $this->assertEquals('512.123', Number::precision(512.123456789, 3));
        $this->assertEquals('512.12', Number::precision(512.123456789, 2));
    }

    public function testpercent()
    {
        $this->assertEquals('33.33%', Number::percent(33.3333333));
        $this->assertEquals('33.33%', Number::percent(0.33333, 2, ['multiply' => true]));
    }

    public function testParse()
    {
        $this->assertEquals(123456789.25, Number::parse('123,456,789.25'));
        $this->assertEquals(123456789.0, Number::parse('123,456,789'));
        $this->assertEquals(123456, Number::parse('123456'));
    }

    public function testFormat()
    {
        Number::locale('es-ES');
        $this->assertSame('1.024,66', Number::format(1024.66));

        $this->assertEquals('1,024.66', Number::format(1024.66, ['locale' => 'en_GB']));

        Number::locale('en_GB');
        $this->assertEquals('1025 KG', Number::format(1024.66, ['pattern' => '0 KG']));
    }
}
