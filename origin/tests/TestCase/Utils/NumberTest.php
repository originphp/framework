<?php
/**
 * OriginPHP Framework
 * Copyright 2018 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Test\Utils;

use Origin\Utils\Number;

class NumberTest extends \PHPUnit\Framework\TestCase
{
    public function testCurrency()
    {
        $this->assertEquals('$1,234.56', Number::currency(1234.56));
        $this->assertEquals('$1,234.56', Number::currency('1,234.56'));
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
        Number::setCurrency('GBP');
        $this->assertEquals('£1,234.56', Number::currency(1234.56));
    }

    public function testPrecision()
    {
        $this->assertEquals('512.12', Number::precision(512.123456789, 2));
        $this->assertEquals('512.12', Number::precision(512.123456789, 2));
    }

    public function testToPercentage()
    {
        $this->assertEquals('33.33%', Number::toPercentage(33.3333333));
        $this->assertEquals('33.33%', Number::toPercentage(0.33333, 2, ['multiply' => true]));
    }

    public function testParse()
    {
        $this->assertEquals(123456789.25, Number::parse('123,456,789.25'));
    }

    public function testFormat()
    {
        Number::setLocale('fr-FR');
        $this->assertEquals('1 024,66', Number::format(1024.66));
    }
}
