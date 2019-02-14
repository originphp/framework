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

namespace Origin\Test\Core;

use Origin\Core\Configure;
use Origin\Core\Dot;

class MockConfigure extends Configure
{
    public static function setDot($dot)
    {
        static::$Dot = $dot;
    }
    public static function getDot()
    {
        return static::$Dot;
    }
}

class ConfigureTest extends \PHPUnit\Framework\TestCase
{
    public function testRead()
    {
        $expected = 'foo';
        Configure::write('Test.value', $expected);
        $this->assertEquals($expected, Configure::read('Test.value'));
        $this->assertEquals(['value'=>$expected], Configure::read('Test'));
    }

    public function testWrite()
    {
        $expected = 'bar';
        Configure::write('Test.value', $expected);
        $this->assertEquals($expected, Configure::read('Test.value'));

        $expected = ['value'=>'ok'];
        Configure::write('Test', $expected);
        $this->assertEquals($expected, Configure::read('Test'));

        Configure::write('debug', 0);
        $this->assertEquals(0, Configure::read('debug'));

        Configure::write('debug', 1);
        $this->assertEquals(1, Configure::read('debug'));
        // Test Multi
        Configure::write('Test.key1', 'one');
        Configure::write('Test.key2', 'two');

        $this->assertEquals('one', Configure::read('Test.key1'));
        $this->assertEquals('two', Configure::read('Test.key2'));
    }

    public function testHas()
    {
        Configure::write('Test.value', 'ok');
        $this->assertTrue(Configure::has('Test.value'));
        $this->assertFalse(Configure::has('Test.bar'));
        $this->assertFalse(Configure::has('nonExistant'));
    }

    public function testDelete()
    {
        Configure::write('Name.key1', 'one');
        $this->assertTrue(Configure::has('Name.key1'));

        Configure::write('Name.key2', 'two');
        $this->assertTrue(Configure::has('Name.key2'));

        Configure::write('Name.key3', 'three');
        $this->assertTrue(Configure::has('Name.key3'));

        Configure::delete('Name.key3');
        $this->assertFalse(Configure::has('Name.key3'));

        $this->assertTrue(Configure::has('Name.key1'));
        $this->assertTrue(Configure::has('Name.key2'));
        
        Configure::delete('Name');
        $this->assertFalse(Configure::has('Name.key1'));
        $this->assertFalse(Configure::has('Name.key2'));
        $this->assertFalse(Configure::has('Name'));
    }

    /**
     * Testing getInstance
     */
    public function testGetInstance()
    {
        $dot = MockConfigure::getDot();
        MockConfigure::setDot(null);
        MockConfigure::init();
        $this->assertInstanceOf(Dot::class, MockConfigure::getDot());
        MockConfigure::setDot($dot);// restore
    }
}
