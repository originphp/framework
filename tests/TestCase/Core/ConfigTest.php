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

use Origin\Core\Dot;
use Origin\Core\Config;
use Origin\Core\Exception\Exception;
use Origin\TestSuite\OriginTestCase;
use Origin\Core\Exception\FileNotFoundException;

class MockConfig extends Config
{
    public static function setDot($dot)
    {
        static::$dot = $dot;
    }
    public static function getDot()
    {
        return static::$dot;
    }
}

class ConfigTest extends OriginTestCase
{
    public function testRead()
    {
        $expected = 'foo';
        Config::write('Test.value', $expected);
        $this->assertEquals($expected, Config::read('Test.value'));
        $this->assertEquals(['value' => $expected], Config::read('Test'));
    }

    public function testWrite()
    {
        $expected = 'bar';
        Config::write('Test.value', $expected);
        $this->assertEquals($expected, Config::read('Test.value'));

        $expected = ['value' => 'ok'];
        Config::write('Test', $expected);
        $this->assertEquals($expected, Config::read('Test'));

        $this->deprecated(function () {
            Config::write('debug', 0);
            $this->assertEquals(0, Config::read('debug'));
    
            Config::write('debug', 1);
            $this->assertEquals(1, Config::read('debug'));
        });
       
        // Test Multi
        Config::write('Test.key1', 'one');
        Config::write('Test.key2', 'two');

        $this->assertEquals('one', Config::read('Test.key1'));
        $this->assertEquals('two', Config::read('Test.key2'));
    }

    public function testHas()
    {
        Config::write('Test.value', 'ok');
        $this->assertTrue(Config::exists('Test.value'));
        $this->assertFalse(Config::exists('Test.bar'));
        $this->assertFalse(Config::exists('nonExistant'));
    }

    public function testDelete()
    {
        Config::write('Name.key1', 'one');
        $this->assertTrue(Config::exists('Name.key1'));

        Config::write('Name.key2', 'two');
        $this->assertTrue(Config::exists('Name.key2'));

        Config::write('Name.key3', 'three');
        $this->assertTrue(Config::exists('Name.key3'));

        Config::delete('Name.key3');
        $this->assertFalse(Config::exists('Name.key3'));

        $this->assertTrue(Config::exists('Name.key1'));
        $this->assertTrue(Config::exists('Name.key2'));
        
        Config::delete('Name');
        $this->assertFalse(Config::exists('Name.key1'));
        $this->assertFalse(Config::exists('Name.key2'));
        $this->assertFalse(Config::exists('Name'));
    }

    /**
     * Testing creating new Dot
     */
    public function testDot()
    {
        $dot = MockConfig::getDot();
        MockConfig::setDot(null);
        MockConfig::exists('foo');
        $this->assertInstanceOf(Dot::class, MockConfig::getDot());
        MockConfig::setDot($dot);// restore
    }

    public function testLoad()
    {
        MockConfig::load('sample-config');

        $this->assertEquals('bar', MockConfig::read('Sample-config.foo'));
        $this->assertEquals('foo', MockConfig::read('Sample-config.bar'));
    }

    public function testLoadPlugin()
    {
        MockConfig::load('Widget.foo');

        $this->assertEquals('value', MockConfig::read('Foo.key'));
    }

    public function testLoadFileNotFound()
    {
        $this->expectException(FileNotFoundException::class);
        $sampleConfig = CONFIG . '/sample-config-does not exist.php';
        MockConfig::load($sampleConfig);
    }

    public function testLoadDoesNotReturnArray()
    {
        $this->expectException(Exception::class);
        $sampleConfig = CONFIG . '/storage.php';
        MockConfig::load($sampleConfig);
    }

    public function testConsume()
    {
        // add data
        $token = uid();
        Config::write('ApiCompany', ['token' => $token]);
        $this->assertEquals($token, Config::read('ApiCompany.token'));
        
        $data = Config::consume('ApiCompany');
        $this->assertEquals($data, ['token' => $token]);
        $this->assertNull(Config::read('ApiCompany'));
    }
}
