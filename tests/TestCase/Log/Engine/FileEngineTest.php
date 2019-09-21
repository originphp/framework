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

use Origin\Log\Engine\FileEngine;

class FileEngineTest extends \PHPUnit\Framework\TestCase
{
    public function testName()
    {
        $this->expectException(\ErrorException::class);
        $engine = new FileEngine(['filename' => 'foo.log']);
        $this->assertEquals('foo.log', $engine->config('filename'));
    }
    public function testDefaultConfig()
    {
        $engine = new FileEngine();
        $this->assertEquals(LOGS, $engine->config('path'));
        $this->assertEquals('application.log', $engine->config('filename'));
        $this->assertEquals([], $engine->config('levels'));
        $this->assertEquals([], $engine->config('channels'));
    }
    public function testLog()
    {
        $engine = new FileEngine();
        $id = uniqid();
        $this->assertTrue($engine->log('error', 'Error code {value}', ['value' => $id]));
        $log = file_get_contents(LOGS . DS .  'application.log');
        $date = date('Y-m-d G:i:s');
        $this->assertContains("[{$date}] application ERROR: Error code {$id}", $log);
    }
}
