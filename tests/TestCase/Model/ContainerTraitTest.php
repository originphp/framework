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

namespace Origin\Test\Model;

use Origin\Model\ContainerTrait;

class ActiveRecord
{
    use ContainerTrait;
}

class SimpleContact extends ActiveRecord
{
    protected $hidden = ['password'];
    protected $virtual = ['full_name'];

    protected function getFullName()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
    protected function setFirstName($value)
    {
        return ucfirst(strtolower($value));
    }
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

    public function testIsDirty()
    {
        $record = new ActiveRecord();
       
        $this->assertFalse($record->isDirty());
        $this->assertFalse($record->isDirty('foo'));
        $record->foo = 'bar';
        $this->assertTrue($record->isDirty());
        $this->assertTrue($record->isDirty('foo'));
        unset($record->foo);
        $this->assertFalse($record->isDirty());
    }

    public function testIsClean()
    {
        $record = new ActiveRecord();
       
        $this->assertTrue($record->isClean());
        $this->assertTrue($record->isClean('foo'));
        $record->foo = 'bar';
        $this->assertFalse($record->isClean());
        $this->assertFalse($record->isClean('foo'));
    }

    public function testChanged()
    {
        $record = new ActiveRecord();
        $record->foo = 'bar'; // this is initial value not changed
        
        $this->assertEmpty($record->changed());
        $this->assertNull($record->changed('foo'));
        $this->assertFalse($record->wasChanged());
        $this->assertFalse($record->wasChanged('foo'));

        # Change original data
        $record->foo = 'foobar';
        $this->assertNotEmpty($record->changed());
        $this->assertEquals('bar', $record->changed('foo'));
        $this->assertTrue($record->wasChanged());
        $this->assertTrue($record->wasChanged('foo'));
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

    public function testName()
    {
        $record = new ActiveRecord();
        $record->name = 'foo';

        $record->name('Record');
        $this->assertEquals('Record', $record->name());
    }

    public function testError()
    {
        $record = new ActiveRecord();
        $record->name = 'foo';

        $this->assertEmpty($record->errors());
        $this->assertIsArray($record->errors());

        $record->error('name', 'Invalid name');
        $this->assertEquals(['name' => ['Invalid name']], $record->errors());
        $this->assertEquals(['Invalid name'], $record->errors('name'));

        $record->reset();
        $this->assertEmpty($record->errors());
    }

    public function testHidden()
    {
        $record = new SimpleContact();
        $record->name = 'Jon';
        $record->password = 'secret';
        $this->assertArrayHasKey('name', $record->toArray());
        $this->assertArrayNotHasKey('password', $record->toArray());
    }

    public function testMutations()
    {
        $record = new SimpleContact();
        $record->first_name = 'jon'; // setFirstName
        $record->last_name = 'snow';
        $this->assertEquals('Jon snow', $record->full_name); //getFullName
    }

    /**
     * @depends testMutations
     */
    public function testVirtual()
    {
        $record = new SimpleContact();
        $record->first_name = 'Jon';
        $record->last_name = 'Snow';
        $this->assertArrayHasKey('full_name', $record->toArray());
        $this->assertEquals('Jon Snow', $record->toArray()['full_name']);
    }

    public function testDirty()
    {
        $record = new SimpleContact();
        $record->name = 'Jon';
        $this->assertEquals(['name'], $record->dirty());
        $record->reset();
        $this->assertEmpty($record->dirty());
    }
}
