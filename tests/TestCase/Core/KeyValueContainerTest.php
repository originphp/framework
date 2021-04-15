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

namespace Origin\Test\Core;

use Origin\Core\KeyValueContainer;
use Origin\TestSuite\OriginTestCase;

class KeyValueContainerTest extends OriginTestCase
{
    public function testGet()
    {
        $kvc = new KeyValueContainer(['foo' => 'bar']);
        $this->assertEquals('bar', $kvc->get('foo'));
        $this->assertNull($kvc->get('bar'));
    }

    public function testSet()
    {
        $kvc = new KeyValueContainer(['foo' => 'bar']);
        $kvc->set('foo', '1234');
        $this->assertEquals('1234', $kvc->get('foo'));
    }

    public function testHas()
    {
        $kvc = new KeyValueContainer();
        $this->assertFalse($kvc->has('foo'));
        $kvc->set('foo', 'bar');
        $this->assertTrue($kvc->has('foo'));
    }

    public function testDelete()
    {
        $kvc = new KeyValueContainer(['foo' => 'bar']);
        $this->assertTrue($kvc->has('foo'));
        $this->assertTrue($kvc->delete('foo'));
        $this->assertFalse($kvc->has('foo'));
        $this->assertFalse($kvc->delete('foo'));
    }

    public function testToArray()
    {
        $this->assertEquals(['foo' => 'bar'], (new KeyValueContainer(['foo' => 'bar']))->toArray());
    }

    public function testList()
    {
        $kvc = new KeyValueContainer(['foo' => 'bar']);
        $this->assertEquals(['foo'], $kvc->list());
    }

    public function testIsEmpty()
    {
        $kvc = new KeyValueContainer(['foo' => 'bar']);
        $this->assertFalse($kvc->isEmpty());
        $kvc->delete('foo');
        $this->assertTrue($kvc->isEmpty());
    }

    public function testArrayAccess()
    {
        $kvc = new KeyValueContainer(['foo' => 'bar']);
        $this->assertEquals('bar', $kvc['foo']);
        $kvc['foo'] = 'chu';
        $this->assertEquals('chu', $kvc['foo']);
        $this->assertTrue(isset($kvc['foo']));

        unset($kvc['foo']);
        $this->assertFalse(isset($kvc['foo']));

        unset($kvc['foo']);
    }
}
