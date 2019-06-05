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

class FtpEngineTest extends \PHPUnit\Framework\TestCase
{
    public function setUp(){
        if(!env('FTP_USERNAME')){
            $this->markTestSkipped('FTP env vars not set');
        }
        Storage::config('ftp',[ 
            'engine' => 'ftp',
            'host' => env('FTP_HOST'),
            'username' => env('FTP_USERNAME'),
            'password' => env('FTP_PASSWORD')
        ]);

        Storage::use('ftp');
    }
    public function testReadWrite(){
        $id = uniqid();

        Storage::write('test.txt', $id );
        Storage::write('folder/another-test.txt',$id );
        $this->assertEquals($id ,Storage::read('test.txt'));
        $this->assertEquals($id ,Storage::read('folder/another-test.txt'));
    }

    /**
     * @depends testReadWrite
     */
    public function testList(){
        
        $contents = Storage::list();
  
        $results = collection($contents)->filter(function($result){
            return in_array($result['name'],['folder/another-test.txt','test.txt']);
        })->toList();

 
        $this->assertEquals('test.txt', $results[1]['name']);
        $this->assertTrue($results[1]['timestamp']>strtotime('-1 minute'));
       
        $this->assertEquals('folder/another-test.txt',$results[0]['name']);
        $this->assertEquals(13, $results[0]['size']);

        $contents = Storage::list('folder');

        $this->assertEquals('another-test.txt',$contents[0]['name']);
        $this->assertEquals(1,count($contents));
    }
   /**
     * @depends testReadWrite
     */
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
   /**
     * @depends testReadWrite
     */
    public function testDeleteFolder(){
       
        Storage::write('delete_me/test.txt',uniqid());
        $this->assertTrue(Storage::exists('delete_me'));
        $this->assertTrue(Storage::exists('delete_me/test.txt'));
        Storage::delete('delete_me');
        $this->assertFalse(Storage::exists('delete_me'));
    }
}
