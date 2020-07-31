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

namespace Origin\Test\Core;

use Origin\Core\ContainerTrait;

class ActiveRecord
{
    use ContainerTrait;
}

class ContainerTraitTest extends \PHPUnit\Framework\TestCase
{
    public function testFunctions()
    {
        $record = new ActiveRecord();
        $record->set('foo', 'bar');
        $this->assertEquals('bar', $record->get('foo'));
        $this->assertTrue($record->has('foo'));
        $this->assertTrue($record->unset('foo'));
        $this->assertFalse($record->has('foo'));
    }

    public function testMagicFunctions()
    {
        $record = new ActiveRecord();
        $record->foo = 'bar';
        $this->assertEquals('bar', $record->foo);
        $this->assertTrue(isset($record->foo));
        unset($record->foo);
        $this->assertFalse(isset($record->foo));
    }

    public function testToArray()
    {
        $record = new ActiveRecord();
        $record->foo = 'bar';
        $this->assertEquals(['foo' => 'bar'], $record->toArray());
    }

    public function testToString()
    {
        $record = new ActiveRecord();
        $record->foo = 'bar';
        $this->assertEquals("{\n    \"foo\": \"bar\"\n}", (string) $record);
    }
}
