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

namespace Origin\Test\Cache;

use Origin\Cache\Cache;
use Origin\Exception\InvalidArgumentException;

class CacheTest extends \PHPUnit\Framework\TestCase
{
    public function testCRD()
    {
        # Create
        Cache::write('foo', 'bar');
        # Read
        $this->assertEquals('bar', Cache::read('foo'));
        $this->assertTrue(Cache::check('foo'));
        # Delete
        Cache::delete('foo');
        $this->assertFalse(Cache::read('foo'));
        $this->assertFalse(Cache::check('foo'));
    }

    public function testEnableDisable()
    {
        Cache::disable();
        Cache::write('foo', 'bar');
        $this->assertFalse(Cache::read('foo'));
        Cache::enable();
        Cache::write('foo', 'bar');
        $this->assertEquals('bar', Cache::read('foo'));
    }

    public function testClear()
    {
        Cache::write('foo', 'bar');
        Cache::clear();
        $this->assertFalse(Cache::read('foo'));
    }

    public function testUnkownConfig()
    {
        $this->expectException(InvalidArgumentException::class);
        Cache::read('foo', 'somewhere-outthere');
    }
}
