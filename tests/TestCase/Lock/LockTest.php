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
namespace Origin\Test\TestCase\Lock;

use LogicException;
use Origin\Lock\Lock;

class LockTest extends \PHPUnit\Framework\TestCase
{
    public function testAcquire()
    {
        $lock = new Lock('test');
        $file = sys_get_temp_dir() . '/test.lock';
        $this->assertFileDoesNotExist($file);
        $this->assertTrue($lock->acquire());
        $this->assertFileExists($file);
        $this->assertEquals((string) getmypid(), trim(file_get_contents($file)));
    }

    public function testAcquireAlreadyLocked()
    {
        $lock = new Lock('test');
        $this->assertTrue($lock->acquire());

        $this->expectException(LogicException::class);
        $lock->acquire();
    }

    public function testAcquireUnableToGetLock()
    {
        $lock1 = new Lock('test');
        $lock2 = new Lock('test');

        $this->assertTrue($lock1->acquire());
        $this->assertFalse($lock2->acquire(false));
    }

    public function testRelease()
    {
        $lock1 = new Lock('test');
        $lock2 = new Lock('test');

        $this->assertTrue($lock1->acquire());
        $this->assertFalse($lock2->acquire(false));
        $lock1->release();
        $this->assertTrue($lock2->acquire(false));
    }

    public function testReleaseNotAcquired()
    {
        $lock = new Lock('test');
        $this->expectException(LogicException::class);
        $lock->release();
    }

    public function testIsAcquired()
    {
        $lock = new Lock('test');
        $this->assertFalse($lock->isAcquired());
        $this->assertTrue($lock->acquire());
        $this->assertTrue($lock->isAcquired());
        $lock->release();
        $this->assertFalse($lock->isAcquired());
    }

    public function testAutoRelease()
    {
        $lock = new Lock('test');
        $this->assertTrue($lock->acquire());
        $this->assertTrue($lock->isAcquired());
        unset($lock);

        $lock = new Lock('test');
        $this->assertTrue($lock->acquire());
        $lock->release();
    }

    protected function setUp(): void
    {
        $file = sys_get_temp_dir() . '/test.lock';
        if (file_exists($file)) {
            unlink($file);
        }
    }
}
