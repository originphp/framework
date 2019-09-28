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

namespace Origin\Test\Http\View\Helper;

use Origin\I18n\I18n;
use Origin\Http\View\View;
use Origin\Http\Request;
use Origin\Http\Response;
use Origin\Http\Controller\Controller;
use Origin\Http\View\Helper\IntlHelper;

class IntlHelperTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $controller = new Controller(new Request(), new Response());
        $this->Intl = new IntlHelper(new View($controller));
    }
    public function testDate()
    {
        $this->assertEquals('01 Jan', $this->Intl->date('2019-01-01 08:52:00', 'dd MMM'));
    }
    public function testTime()
    {
        $this->assertEquals('8:52', $this->Intl->time('2019-01-01 08:52:00', 'H:mm'));
    }
    public function testDateTime()
    {
        $this->assertEquals('01 Jan, 2019 8:52', $this->Intl->datetime('2019-01-01 08:52:00', 'dd MMM, y H:mm'));
    }
    public function testCurrency()
    {
        $this->assertSame('£1,000.01', $this->Intl->currency(1000.010101010, 'GBP'));
    }
    public function testNumber()
    {
        $this->assertSame('234,567,890.102', $this->Intl->number(234567890.1020304050));
    }
    public function testDecimal()
    {
        $this->assertSame('234,567,890.01', $this->Intl->precision(234567890.0123));
        $this->assertSame('100', $this->Intl->precision(100)); // This is different behavior than wanted. Should be .00
    }
    public function testPercent()
    {
        $this->assertSame('33.33%', $this->Intl->percent(33.333333));
    }

    public function testLocales()
    {
        I18n::initialize(['locale' => 'en_GB']);
 
        $locales = $this->Intl->locales();
        $this->assertEquals('English (United Kingdom)', $locales['en_GB']); // test with null value
    }
    public function testTimezones()
    {
        $timezones = $this->Intl->timezones();
      
        $this->assertEquals('GMT +00:00 - UTC', $timezones['UTC']);
        $this->assertEquals(426, count($timezones)); // not sure if this varys on differen like locales
    }
}
