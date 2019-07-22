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

namespace Origin\Test\Storage\Engine;

use Origin\Exception\NotFoundException;

trait EngineTestTrait
{
    public function testWrite()
    {
        $data = 'local-engine' . (string) time();

        $this->assertTrue($this->engine()->write('foo.txt', $data));
        $this->assertTrue($this->engine()->write('folder/bar.txt', $data));
        $this->assertTrue($this->engine()->write('folder/subfolder/foobar.txt', $data));
        return $data;
    }
    /**
     * @depends testWrite
     */
    public function testExists($data)
    {
        $this->assertTrue($this->engine()->exists('foo.txt'));
        $this->assertTrue($this->engine()->exists('folder/bar.txt'));
        $this->assertTrue($this->engine()->exists('folder'));
        $this->assertTrue($this->engine()->exists('folder/subfolder/foobar.txt'));
    }

    /**
     * @depends testWrite
     */
    public function testRead($data)
    {
        $this->assertEquals($data, $this->engine()->read('foo.txt'));
        $this->assertEquals($data, $this->engine()->read('folder/bar.txt'));
        $this->assertEquals($data, $this->engine()->read('folder/subfolder/foobar.txt'));
        $this->expectException(NotFoundException::class);
        $this->engine()->read('passwords.txt');
    }

    public function testList()
    {
        $files = $this->engine()->list();

        // Test Format
        $foo = $this->getFile('foo.txt', $files);
        $expected = [
            'name' => 'foo.txt',
            'timestamp' => 1559996145,
            'size' => 22,
        ];
        $this->assertEquals($expected, $foo);
        // Test Contents
        $this->assertHasFileInList('foo.txt', $files);
        $this->assertHasFileInList('folder/bar.txt', $files);
        $this->assertHasFileInList('folder/subfolder/foobar.txt', $files);

        
        $files = $this->engine()->list('folder');
        $this->assertHasFileInList('bar.txt', $files);
        $this->assertHasFileInList('subfolder/foobar.txt', $files);

                
        $files = $this->engine()->list('folder/subfolder');
        $this->assertHasFileInList('foobar.txt', $files);

        $this->expectException(NotFoundException::class);
        $this->engine()->list('a-folder-that-does-not-exist');
    }

    /**
     * @depends testExists
     *
     */
    public function testDelete()
    {
        $this->assertTrue($this->engine()->delete('foo.txt'));
        $this->assertFalse($this->engine()->delete('folder/')); // Test Protection
        $this->assertTrue($this->engine()->delete('folder/bar.txt'));
        $this->assertTrue($this->engine()->delete('folder/subfolder/foobar.txt'));

        $this->assertFalse($this->engine()->exists('foo.txt'));
        $this->assertFalse($this->engine()->exists('folder/bar.txt'));
        $this->assertFalse($this->engine()->exists('folder/subfolder/foobar.txt'));

        $this->expectException(NotFoundException::class);
        $this->assertFalse($this->engine()->delete('folder/passwords.txt'));
    }

    /**
     * @depends testDelete
     *
     */
    public function testDeleteFolder()
    {
        $loremipsum = 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor.';
        $this->assertTrue($this->engine()->write('docs/foo.txt', $loremipsum));
        $this->assertTrue($this->engine()->write('docs/bar.txt', $loremipsum));
        $this->assertTrue($this->engine()->write('docs/dota2/natures_profit.txt', $loremipsum));
        $this->assertTrue($this->engine()->write('docs/dota2/lion.txt', $loremipsum));
        
        $this->assertTrue($this->engine()->delete('docs/dota2'));
        $this->assertFalse($this->engine()->exists('docs/dota2/natures_profit.txt'));

        // Add back to test deeper deleting
        $this->assertTrue($this->engine()->write('docs/dota2/natures_profit.txt', $loremipsum));
        $this->assertTrue($this->engine()->delete('docs'));
        $this->assertFalse($this->engine()->exists('docs/dota2/natures_profit.txt'));
    }


    protected function assertHasFileInList(string $filename, array $files)
    {
        foreach ($files as $file) {
            if ($file['name'] == $filename) {
                return true;
            }
        }
        return false;
    }

    protected function getFile(string $filename, array $files)
    {
        foreach ($files as $file) {
            if ($file['name'] == $filename) {
                if ($file['timestamp']>strtotime('-1 minute')) {
                    $file['timestamp'] = 1559996145; // Standardize
                }
                return $file;
            }
        }
        return null;
    }
}
