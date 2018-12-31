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

namespace Origin\Test\Model;

use Origin\Model\Model;
use Origin\Model\ModelRegistry;
use Origin\TestSuite\TestTrait;

class MockModelRegistry extends ModelRegistry
{
    public static function registry()
    {
        return static::$registry;
    }

    public static function resetInstance()
    {
        static::$registry = [];
        static::$config = [];
    }
}

class MockModel extends Model
{
    use TestTrait;
}

class ModelRegistryTest extends \PHPUnit\Framework\TestCase
{
    public function testConfig()
    {
        MockModelRegistry::resetInstance(); // Reset For Test
        $config = ['datasource' => 'test'];
        MockModelRegistry::config('User', $config);
        $this->assertEquals(['User' => $config], MockModelRegistry::config());
        $this->assertEquals($config, MockModelRegistry::config('User'));
    }

    public function testSet()
    {
        MockModelRegistry::resetInstance(); // Reset For Test
        MockModelRegistry::set('Mock', new MockModel());
        $registry = MockModelRegistry::registry();
        $this->assertArrayHasKey('Mock', $registry);
    }

    /**
     * @depends testSet
     */
    public function testHas()
    {
        MockModelRegistry::resetInstance(); // Reset For Test
        MockModelRegistry::set('Mock', new MockModel());
        $this->assertTrue(MockModelRegistry::has('Mock'));
        $this->assertFalse(MockModelRegistry::has('NonExistant'));
    }

    /**
     * @depends testHas
     */
    public function testClear()
    {
        MockModelRegistry::resetInstance(); // Reset For Test
        MockModelRegistry::set('Mock', new MockModel());
        $this->assertTrue(MockModelRegistry::has('Mock'));
        MockModelRegistry::reset();
        $this->assertFalse(MockModelRegistry::has('Mock'));
    }

    /**
     * @depends testGet
     */
    public function testGet()
    {
        MockModelRegistry::set('Mock', new MockModel());
        $this->assertEquals($MockModel, MockModelRegistry::get('Mock'));
    }

    public function testGetConfig()
    {
        MockModelRegistry::reset();
        $config = ['datasource' => 'testGetConfig'];
        MockModelRegistry::config('Origin\Test\Model\MockModel', $config);
        $MockModel = MockModelRegistry::get('Origin\Test\Model\MockModel');
        $this->assertEquals('testGetConfig', $MockModel->datasource);
    }

    /**
     * @depends testSet
     */
    public function testDelete()
    {
        MockModelRegistry::reset();

        MockModelRegistry::set('Mock', new MockModel());

        $this->assertTrue(MockModelRegistry::delete('Mock'));
        $this->assertFalse(MockModelRegistry::has('Mock'));
        $this->assertFalse(MockModelRegistry::delete('Mock'));
    }
}
