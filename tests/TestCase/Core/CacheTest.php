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

use Origin\Core\Cache;

class SimpleObject
{
}

class CacheTest extends \PHPUnit\Framework\TestCase
{
    public function testInternalCache()
    {
        $now = now();
        $id = uniqid();
        $this->assertTrue(Cache::set($id, $now));
        $this->assertEquals($now, Cache::get($id));
        $this->assertTrue(Cache::set($id, $now, ['duration' => 0]));
        $this->assertNull(Cache::get($id));
    }

    /**
     * @depends testInternalCache
     */
    public function testInternalCacheTypes()
    {
        $now = now();
        $id = uniqid();
        $data = new SimpleObject();
        $data->now = $now;

        $data2 = new \StdClass;
        $data2->now = $now;
        // Test objects
        $this->assertTrue(Cache::set($id, $data));
        $this->assertEquals($data, Cache::get($id));

        $this->assertTrue(Cache::set($id, $data2, ['serialize' => false]));
        $this->assertEquals($data2, Cache::get($id));

        // Test Array
        $data = ['key' => 'value'];
        $this->assertTrue(Cache::set($id, $data));
        $this->assertEquals($data, Cache::get($id));

        $this->assertTrue(Cache::set($id, $data, ['serialize' => false]));
        $this->assertEquals($data, Cache::get($id));

        $data = [];
        for ($i = 0;$i < 5;$i++) {
            $obj = new \StdClass;
            $obj->now = time();
            $data[] = $obj;
        }

        $this->assertTrue(Cache::set($id, $data));
        $this->assertEquals($data, Cache::get($id));

        $this->assertTrue(Cache::set($id, $data, ['serialize' => false]));
        $this->assertEquals($data, Cache::get($id));
    }
}
