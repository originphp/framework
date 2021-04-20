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

use Origin\Core\Bag;
use Origin\TestSuite\OriginTestCase;

class BagTest extends OriginTestCase
{
    public function testGet()
    {
        $bag = new Bag(['foo' => 'bar']);
        $this->assertEquals('bar', $bag->get('foo'));
        $this->assertEquals('bar', $bag->foo);

        $this->assertNull($bag->get('bar'));
        $this->assertNull($bag->bar);
        
        $this->assertEquals('not-found', $bag->get('bar', 'not-found'));
    }

    public function testSet()
    {
        $bag = new Bag(['foo' => 'bar']);
        $bag->set('foo', '1234');
        $this->assertEquals('1234', $bag->get('foo'));

        $bag->bar = 'foo';
        $this->assertEquals('foo', $bag->bar);
    }

    public function testHas()
    {
        $bag = new Bag();
        $this->assertFalse($bag->has('foo'));
        $this->assertFalse(isset($bag->foo));

        $bag->set('foo', 'bar');
        $this->assertTrue($bag->has('foo'));
        $this->assertTrue(isset($bag->foo));
    }

    public function testRemove()
    {
        $bag = new Bag(['foo' => 'bar']);
        $this->assertTrue($bag->has('foo'));
        $this->assertTrue($bag->remove('foo'));

        $this->assertFalse($bag->has('foo'));
        $this->assertFalse($bag->remove('foo'));

        $bag = new Bag(['foo' => 'bar']);
        $this->assertTrue($bag->has('foo'));
        unset($bag->foo);
        $this->assertFalse($bag->has('foo'));
    }

    public function testToArray()
    {
        $this->assertEquals(['foo' => 'bar'], (new Bag(['foo' => 'bar']))->toArray());
    }

    public function testToString()
    {
        $bag = new Bag(['foo' => 'bar']);
        $this->assertEquals('{"foo":"bar"}', (string) $bag);
    }

    public function testToJson()
    {
        $bag = new Bag(['foo' => 'bar']);
        $this->assertEquals('{"foo":"bar"}', $bag->toJson());
      
        $this->assertEquals("{\n    \"foo\": \"bar\"\n}", $bag->toJson(['pretty' => true]));
    }

    public function testCount()
    {
        $bag = new Bag();
        $this->assertEquals(0, $bag->count());
        $bag->foo = 'bar';
        $this->assertEquals(1, $bag->count());
    }

    public function testGetIterator()
    {
        $bag = new Bag(['foo' => 'bar']);
        $this->assertEquals(['foo' => 'bar'], iterator_to_array($bag));
    }

    public function testList()
    {
        $bag = new Bag(['foo' => 'bar']);
        $this->assertEquals(['foo'], $bag->list());
    }

    public function testSerializeUnserialize()
    {
        $bag = new Bag(['foo' => 'bar']);
        $serialized = serialize($bag);
        $this->assertEquals('C:15:"Origin\Core\Bag":26:{a:1:{s:3:"foo";s:3:"bar";}}', $serialized);
        $this->assertEquals($bag, unserialize($serialized));
    }

    public function testArrayAccess()
    {
        $bag = new Bag(['foo' => 'bar']);
        $this->assertEquals('bar', $bag['foo']);
        $bag['foo'] = 'chu';
        $this->assertEquals('chu', $bag['foo']);
        $this->assertTrue(isset($bag['foo']));

        unset($bag['foo']);
        $this->assertFalse(isset($bag['foo']));

        unset($bag['foo']);
    }
}
