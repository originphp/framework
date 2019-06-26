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

use Origin\Engine\Storage\FtpEngine;
use Origin\Test\Engine\Storage\EngineTestTrait;

include_once 'EngineTestTrait.php';

class FtpEngineTest extends \PHPUnit\Framework\TestCase
{
    use EngineTestTrait;

    protected function setUp(): void
    {
        if (!env('FTP_HOST')) {
            $this->markTestSkipped('FTP env vars not set');
        }
    }

    public $engine = null;

    public function engine()
    {
        if ($this->engine === null) {
            $this->engine =  new FtpEngine([
                'host' => env('FTP_HOST'),
                'username' => env('FTP_USERNAME'),
                'password' => env('FTP_PASSWORD')
            ]);
        }
        return $this->engine;
    }
    public function testConfig()
    {
        $expected = [
            'host' => 'ftp',
            'username' => 'ftp',
            'password' => 'ftp',
            'port' => '21',
            'root' => '/',
            'timeout' => 10,
            'ssl' => false,
            'passive' => false,
        ];

        $this->assertEquals($expected, $this->engine()->config());
    }
}
