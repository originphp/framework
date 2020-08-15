<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
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

use Origin\Core\PhpFile;
use Origin\Core\Exception\Exception;
use Origin\Core\Exception\InvalidArgumentException;

class PhpFileTest extends \PHPUnit\Framework\TestCase
{
    public function testReadWrite()
    {
        $filename = tmp_path(uid());
        $data = ['foo' => 'bar'];
        $file = new PhpFile();
        $file->write($filename, $data);
        $this->assertSame($data, $file->read($filename));
    }

    /**
     * Test export using nested array to short synax, and read again to see if it was valid
     *
     * @return void
     */
    public function testWriteShort()
    {
        $filename = tmp_path(uid());
        $data = [
            'foo' => 'bar',
            'empty' => [],
            'bar' => [
                'foo' => 'bar',
                'bar' => [
                    'foo' => 'bar',
                    'empty' => [],
                ]
            ]
        ];
        $file = new PhpFile();
        $file->write($filename, $data, ['short' => true]);

        $this->assertSame($data, $file->read($filename));
    }

    public function testNotFound()
    {
        $this->expectException(InvalidArgumentException::class);
        $file = new PhpFile();
        $file->read('/var/www/foo.txt');
    }

    public function testInvalidFile()
    {
        $filename = tmp_path(uid());
        file_put_contents($filename, '.');

        $this->expectException(Exception::class);
        $file = new PhpFile();
        $file->read($filename);
    }
}
