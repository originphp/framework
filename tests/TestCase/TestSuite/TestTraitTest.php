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

namespace Origin\Test\TestSuite;

use Origin\TestSuite\TestTrait;

class FunkyClass
{
    use TestTrait;
    protected $name = 'FunkyClass';

    protected function calculate($x, $y)
    {
        return $x + $y;
    }
}

class TestTraitTest extends \PHPUnit\Framework\TestCase
{
    public function testGetProperty()
    {
        $FunkyClass = new FunkyClass();
        $this->assertEquals('FunkyClass', $FunkyClass->getProperty('name'));
        $this->assertNull($FunkyClass->getProperty('nonExistant'));
    }

    /**
     * @depends testGetProperty
     */
    public function testSetProperty()
    {
        $FunkyClass = new FunkyClass();
        $FunkyClass->setProperty('foo', 'bar');
        $this->assertEquals('bar', $FunkyClass->getProperty('foo'));
    }

    public function testCallMethod()
    {
        $FunkyClass = new FunkyClass();
        $this->assertEquals(3, $FunkyClass->callMethod('calculate', [1, 2]));
    }
}
