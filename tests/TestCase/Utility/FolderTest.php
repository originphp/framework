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

use Origin\Utility\Folder;
use Origin\Exception\NotFoundException;

class FolderTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        $tmp = sys_get_temp_dir() . DS . uniqid();
        $this->assertTrue(Folder::create($tmp));
        $this->assertFalse(Folder::create($tmp . '/depth1/depth2/depth3'));
        $this->assertTrue(Folder::create($tmp  . '/depth1/depth2/depth3', true));
    }

    public function testExists()
    {
        $this->assertTrue(Folder::exists(CONFIG));
        $this->assertFalse(Folder::exists('/foo'));
    }

    public function testList()
    {
        $tmp = sys_get_temp_dir() . DS . uniqid();
        $this->assertTrue(Folder::create($tmp  . '/depth1/depth2/depth3', true));
        file_put_contents($tmp  . '/depth1/depth2/foo.txt', 'bar');

        $result = Folder::list($tmp . '/depth1/depth2');

        $this->assertEquals('foo.txt', $result[0]['name']);
        $this->assertEquals('file', $result[0]['type']);


        $result = Folder::list($tmp  . '/depth1/depth2', true);
    
        $this->assertEquals('depth3', $result[0]['name']);
        $this->assertEquals('directory', $result[0]['type']);

        $this->assertEquals('foo.txt', $result[1]['name']);
        $this->assertEquals('file', $result[1]['type']);
    }

  
    public function testListException()
    {
        $this->expectException(NotFoundException::class);
        Folder::list('/foo');
    }

    /**
     * @depends testCreate
     *
     * @return void
     */
    public function testDelete()
    {
        $tmp = sys_get_temp_dir() . DS . uniqid();
        $this->assertTrue(Folder::create($tmp  . '/depth1/depth2/depth3', true));
        file_put_contents($tmp  . '/depth1/depth2/foo.txt', 'bar');
        $this->assertFalse(Folder::delete($tmp  . '/depth1/depth2'));
        $this->assertTrue(Folder::delete($tmp  . '/depth1/depth2', true));
        $this->assertFalse(Folder::exists($tmp  . '/depth1/depth2'));
    }

    public function testDeleteException()
    {
        $this->expectException(NotFoundException::class);
        Folder::delete('/foo');
    }

    /**
     * @depends testCreate
     *
     */
    public function testCopy()
    {
        $tmp = sys_get_temp_dir() . DS . uniqid() . DS;
        $this->assertTrue(Folder::create($tmp . 'docs' . DS  .'archive', true));
        file_put_contents($tmp . 'docs' . DS  . 'file1.txt', 'foo');
        file_put_contents($tmp . 'docs' . DS  .'archive' . DS . 'file2.txt', 'foo');
        $this->assertTrue(Folder::copy($tmp . 'docs', 'docs2'));
    
        $this->assertTrue(file_exists($tmp . 'docs2' . DS . 'file1.txt'));
        $this->assertTrue(file_exists($tmp . 'docs2' . DS  .'archive' . DS . 'file2.txt'));
    }

    public function testCopyException()
    {
        $this->expectException(NotFoundException::class);
        Folder::copy('/foo', 'bar');
    }

    public function testRename()
    {
        $tmp = sys_get_temp_dir() . DS . uniqid() . DS;
        $this->assertTrue(Folder::create($tmp . 'docs' . DS  .'archive', true));
        $this->assertTrue(Folder::rename($tmp . 'docs', 'docs-again'));
        $this->assertFalse(Folder::exists($tmp . 'docs'));
        $this->assertTrue(Folder::exists($tmp . 'docs-again'));
    }

    public function testRenameException()
    {
        $this->expectException(NotFoundException::class);
        Folder::rename('/foo', 'bar');
    }

    public function testMove()
    {
        $tmp = sys_get_temp_dir() . DS . uniqid() . DS;
        $this->assertTrue(Folder::create($tmp . 'docs' . DS  .'archive', true));
        $this->assertTrue(Folder::move($tmp . 'docs', 'docs-again'));
        $this->assertFalse(Folder::exists($tmp . 'docs'));
        $this->assertTrue(Folder::exists($tmp . 'docs-again'));
    }

    public function testMoveException()
    {
        $this->expectException(NotFoundException::class);
        Folder::move('/foo', 'bar');
    }


    public function testPermissions()
    {
        $tmp = sys_get_temp_dir() . DS . uniqid() . DS;
        Folder::create($tmp, false, 0644);

        $this->assertEquals('0644', Folder::permissions($tmp));

        $this->assertTrue(Folder::chmod($tmp, 0775));
        clearstatcache(); // stat stuff is cached, so for next assert to work clear cache
        $this->assertEquals('0775', Folder::permissions($tmp));
    }

    public function testPermissionsException()
    {
        $this->expectException(NotFoundException::class);
        Folder::permissions('/foo');
    }


    public function testOwner()
    {
        $tmp = sys_get_temp_dir() . DS . uniqid() . DS;
        Folder::create($tmp);

        $this->assertEquals('root', Folder::owner($tmp));
        $this->assertTrue(Folder::chown($tmp, 'www-data'));
        clearstatcache(); // stat stuff is cached, so for next assert to work clear cache
        $this->assertEquals('www-data', Folder::owner($tmp));
    }

    public function testOwnerException()
    {
        $this->expectException(NotFoundException::class);
        Folder::owner('/foo');
    }


    public function testGroup()
    {
        $tmp = sys_get_temp_dir() . DS . uniqid() . DS;
        Folder::create($tmp);

        $this->assertEquals('root', Folder::group($tmp));
        $this->assertTrue(Folder::chgrp($tmp, 'www-data'));
        clearstatcache(); // stat stuff is cached, so for next assert to work clear cache
        $this->assertEquals('www-data', Folder::group($tmp));
    }

    public function testGroupException()
    {
        $this->expectException(NotFoundException::class);
        Folder::group('/foo');
    }

    public function testChmodException()
    {
        $this->expectException(NotFoundException::class);
        Folder::chmod('/foo',0775);
    }

    public function testChownException()
    {
        $this->expectException(NotFoundException::class);
        Folder::chown('/foo','some-user');
    }

    public function testChgrpException()
    {
        $this->expectException(NotFoundException::class);
        Folder::chgrp('/foo','some-group');
    }
}
