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

use Origin\Log\Engine\ConsoleEngine;
use Origin\TestSuite\Stub\ConsoleOutput;

class MockConsoleEngine extends ConsoleEngine
{
    public function setConsoleOutput(ConsoleOutput $consoleOutput)
    {
        $this->output = $consoleOutput;
    }
    public function getConsoleOutput()
    {
        return $this->output;
    }
}
class ConsoleEngineTest extends \PHPUnit\Framework\TestCase
{
    public function testDefaultConfig()
    {
        $engine = new MockConsoleEngine();
        $this->assertEquals('php://stderr', $engine->config('stream'));
        $this->assertEquals([], $engine->config('levels'));
        $this->assertEquals([], $engine->config('channels'));
    }
    public function testLog()
    {
        $engine = new MockConsoleEngine();
        $bufferedOutput = new ConsoleOutput();
        $engine->setConsoleOutput($bufferedOutput); // Buffered;
        $id = uniqid();
        $engine->log('error', 'Error code {value}', ['value'=>$id]);
        $date = date('Y-m-d G:i:s');
        $this->assertContains("[{$date}] application ERROR: Error code {$id}", $bufferedOutput->read());
    }
}
