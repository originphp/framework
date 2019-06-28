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

namespace Origin\Test\Storage;

use Origin\Storage\Storage;
use Origin\Exception\InvalidArgumentException;

class StorageTest extends \PHPUnit\Framework\TestCase
{
    public function testCRD()
    {
        Storage::config('tmp', [
            'engine'=>'Local',
            'root' => TMP
            ]);

        $file = uniqid();
        # Create
        $this->assertTrue(Storage::write($file, 'bar', ['config'=>'tmp']));
        $this->assertFileExists(TMP . DS . $file); // Check using correct config
        # Read
        $this->assertEquals('bar', Storage::read($file, ['config'=>'tmp']));
        $this->assertTrue(Storage::exists($file, ['config'=>'tmp']));
        # Delete
        Storage::delete($file, ['config'=>'tmp']);
        $this->assertFalse(Storage::exists($file, ['config'=>'tmp']));
    }

    public function testUnkownConfig()
    {
        $this->expectException(InvalidArgumentException::class);
        Storage::volume('somewhere-out-there');
    }
}
