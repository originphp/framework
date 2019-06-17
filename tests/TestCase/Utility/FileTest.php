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

namespace Origin\Test\Utility;

use Origin\Utility\File;
use Origin\Exception\NotFoundException;

class FileTest extends \PHPUnit\Framework\TestCase
{
    public function testRead()
    {
        $tmp = sys_get_temp_dir() . DS . uniqid();
        file_put_contents($tmp, 'Hello World!');
        $this->assertEquals('Hello World!', File::read($tmp));
    }

    public function testReadException()
    {
        $this->expectException(NotFoundException::class);
        File::read('/foo/bar.txt');
    }
 

    public function testInfoException()
    {
        $this->expectException(NotFoundException::class);
        File::info('/foo/bar.txt');
    }

    public function testWrite()
    {
        $tmp = sys_get_temp_dir() . DS  . uniqid();
        $this->assertTrue(File::write($tmp, 'foo'));
        $this->assertEquals('foo', file_get_Contents($tmp));
        $this->assertFalse(File::write(sys_get_temp_dir() . DS . 'does-not-exist' . DS  .'file.txt', 'foo'));
    }
 
    public function testAppend()
    {
        $tmp = sys_get_temp_dir() . DS . uniqid();
        $this->assertTrue(File::append($tmp, 'foo'));
        $this->assertEquals('foo', file_get_Contents($tmp));
        $this->assertTrue(File::append($tmp, 'bar'));
        $this->assertEquals('foobar', file_get_Contents($tmp));
        $this->assertFalse(File::append(sys_get_temp_dir() . DS . 'does-not-exist' . DS  .'file.txt', 'foo'));
    }

    public function testExists()
    {
        $tmp = sys_get_temp_dir() . DS . uniqid();
        File::write($tmp, 'foo');
        $this->assertTrue(File::exists($tmp));
        $this->assertFalse(File::exists('/foo/bar.txt'));
    }

    /**
     * @depends testExists
     *
     * @return void
     */
    public function testDelete()
    {
        $tmp = sys_get_temp_dir() . DS . uniqid();
        file_put_contents($tmp, 'foo');
        $this->assertTrue(File::delete($tmp));
        $this->expectException(NotFoundException::class);
        File::delete($tmp);
    }
 
    public function testTmp()
    {
        $data = 'Touch base wiggle room, but mobile friendly, nor blue sky.';
        $filename = File::tmp($data);
        $this->assertTrue(strpos($filename, sys_get_temp_dir()) !== false);
        $this->assertEquals($data, file_get_contents($filename));
    }

    public function testInfo()
    {
        $data = base64_encode(openssl_random_pseudo_bytes(1000000));
        $filename = uniqid() . '.txt';
        $tmpfile = sys_get_temp_dir() . DS . $filename;
        File::write($tmpfile, $data);
        $expected = [
            'path' => '/tmp',
            'filename' => $filename,
            'extension' => 'txt',
            'type' => 'text/plain',
            'size' => '1333336',
            'timestamp' => fileatime($tmpfile)
        ];
        $this->assertEquals($expected, File::info($tmpfile));
    }
 
    /**
     * @depends testTmp
     */
    public function testRename()
    {
        $data = 'What are the expectations pulling teeth gain traction put a record on and see who dances onward and upward, productize the deliverables and focus on the bottom line.';
        $filename = File::tmp($data);
        $this->assertTrue(File::rename($filename, 'rename.txt'));
        $this->assertEquals($data, file_get_contents(sys_get_temp_dir() . DS  . 'rename.txt'));
        unlink(sys_get_temp_dir() . DS  . 'rename.txt');
    }

    public function testRenameException()
    {
        $this->expectException(NotFoundException::class);
        File::rename('/foo/bar.txt', 'fozzy.txt');
    }

    /**
     * @depends testTmp
     */
    public function testCopy()
    {
        $data = 'Moving the goalposts thought shower deploy, and that jerk from finance really threw me under the bus, nor critical mass fire up your browser.';
        $filename = File::tmp($data);
        $this->assertTrue(File::copy($filename, 'copied.txt'));
        $this->assertEquals($data, file_get_contents(sys_get_temp_dir() . DS  . 'copied.txt'));
        unlink(sys_get_temp_dir() . DS  . 'copied.txt');
    }

    public function testCopyException()
    {
        $this->expectException(NotFoundException::class);
        File::copy('/foo/bar.txt', 'fozzy.txt');
    }

    /**
     * @depends testTmp
     */
    public function testMove()
    {
        $data = 'Cannibalize bleeding edge, for net net.';
        $filename = File::tmp($data);
        $this->assertTrue(File::move($filename, sys_get_temp_dir() . DS  .'moved.txt'));
        $this->assertFalse(file_exists($filename));
        $this->assertEquals($data, file_get_contents(sys_get_temp_dir() . DS  . 'moved.txt'));
        unlink(sys_get_temp_dir() . DS  . 'moved.txt');
    }

    public function testMoveException()
    {
        $this->expectException(NotFoundException::class);
        File::move('/foo/bar.txt', 'fozzy.txt');
    }
    
    public function testPerms()
    {
        $data = 'Not really important';
        $filename = File::tmp($data);
     
        $this->assertEquals('0644', File::perms($filename));

        $this->assertTrue(File::chmod($filename, 0775));
        clearstatcache(); // stat stuff is cached, so for next assert to work clear cache
        $this->assertEquals('0775', File::perms($filename));
    }

    public function testPermsException()
    {
        $this->expectException(NotFoundException::class);
        File::perms('/foo/bar.txt');
    }


    public function testOwner()
    {
        $data = 'Not really important';
        $filename = File::tmp($data);
        $this->assertEquals('root', File::owner($filename));
        $this->assertTrue(File::chown($filename, 'www-data'));
        clearstatcache(); // stat stuff is cached, so for next assert to work clear cache
        $this->assertEquals('www-data', File::owner($filename));
    }

    public function testOwnerException()
    {
        $this->expectException(NotFoundException::class);
        File::owner('/foo/bar.txt');
    }

  
    public function testGroup()
    {
        $data = 'Not really important';
        $filename = File::tmp($data);
        $this->assertEquals('root', File::group($filename));
        $this->assertTrue(File::chgrp($filename, 'www-data'));
        clearstatcache(); // stat stuff is cached, so for next assert to work clear cache
        $this->assertEquals('www-data', File::group($filename));
    }
    public function testGroupException()
    {
        $this->expectException(NotFoundException::class);
        File::group('/foo/bar.txt');
    }

    public function testChmodException()
    {
        $this->expectException(NotFoundException::class);
        File::chmod('/foo/file.txt', 0775);
    }

    public function testChownException()
    {
        $this->expectException(NotFoundException::class);
        File::chown('/foo/file.txt', 'some-user');
    }

    public function testChgrpException()
    {
        $this->expectException(NotFoundException::class);
        File::chgrp('/foo/file.txt', 'some-group');
    }
}
