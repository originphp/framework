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

use Origin\Core\Exception\Exception;
use Origin\Core\Exception\InvalidArgumentException;
use Origin\Core\PhpFile;

class PhpFileTest extends \PHPUnit\Framework\TestCase
{
    public function testReadWrite()
    {
        $tmp = sys_get_temp_dir() . '/' . uniqid();
        $data = ['foo' => 'bar'];
        $file = new PhpFile();
        $file->write($tmp, $data);
        $this->assertSame($data, $file->read($tmp));
    }

    public function testNotFound()
    {
        $this->expectException(InvalidArgumentException::class);
        $file = new PhpFile();
        $file->read('/var/www/foo.txt');
    }

    public function testInvalidFile()
    {
        $this->expectException(Exception::class);
        $file = new PhpFile();
        $file->read(ORIGIN . '/src/README.md');
    }
}
