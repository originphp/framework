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

namespace Origin\Test\View\Helper;

use Origin\View\View;
use Origin\Http\Request;
use Origin\Http\Response;
use Origin\Controller\Controller;
use Origin\View\Helper\NumberHelper;

class NumberHelperTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $controller = new Controller(new Request(), new Response());
        $this->Number = new NumberHelper(new View($controller));
    }
   
    public function testCurrency()
    {
        $this->assertSame('£1,000.01', $this->Number->currency(1000.010101010, 'GBP'));
    }
    public function testNumber()
    {
        $this->assertSame('234,567,890.10', $this->Number->format(234567890.1020304050));
    }
    public function testDecimal()
    {
        $this->assertSame('234,567,890.00', $this->Number->precision(234567890));
    }
    public function testPercent()
    {
        $this->assertSame('33.33%', $this->Number->percent(33.333333));
    }
}
