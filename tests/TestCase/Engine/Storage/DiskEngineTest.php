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

namespace Origin\Test\Engine\Storage;

use Origin\Utility\Storage;

class DiskEngineTest extends \PHPUnit\Framework\TestCase
{

    public function testConfig()
    {
        $config = Storage::config('default');
        $this->assertEquals('Origin\Engine\Storage\DiskEngine',$config['className']);
        $this->assertEquals(APP .DS . 'storage',$config['path']);
      
    }

    public function testReadWrite(){
        $id = uniqid();
        $filename = APP . DS . 'storage' . DS  .'test.txt';
        Storage::write('test.txt', $id );
        $this->assertEquals( $id ,file_get_contents($filename));
        Storage::write('folder/another-test.txt',$id );
        $this->assertEquals($id ,Storage::read('test.txt'));
        $this->assertEquals($id ,Storage::read('folder/another-test.txt'));
    }

    public function testList(){
        $this->assertEquals(['folder/another-test.txt','test.txt'],Storage::list());
        $this->assertEquals(['another-test.txt'],Storage::list('folder'));
    }

    public function testExists(){
        $this->assertTrue(Storage::exists('test.txt'));
        $this->assertFalse(Storage::exists('tests.txt'));
        $this->assertTrue(Storage::exists('folder/another-test.txt'));
    }
    /**
     * @depends testExists
     */
    public function testDelete(){
        Storage::delete('test.txt');
        $this->assertFalse(Storage::exists('test.txt'));
       
        Storage::delete('folder/another-test.txt');
        $this->assertFalse(Storage::exists('folder/another-test.txt'));

    }

    public function testDeleteFolder(){
       
        Storage::write('delete_me/test.txt',uniqid());
        $this->assertTrue(Storage::exists('delete_me/test.txt'));
        Storage::delete('delete_me');
        $this->assertEquals([],Storage::list());
    }
}
