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

use Origin\Log\Engine\SyslogEngine;

class MockSyslogEngine extends SyslogEngine
{
    protected $written = null;
    protected function openlog(string $identity, int $option = null, int $facility = null) :bool
    {
        return true;
    }
    protected function write(int $priority, string $message) :bool
    {
        $this->written = $priority . ':' . $message;

        return true;
    }
    public function written() : string
    {
        return $this->written;
    }
}
class SyslogEngineTest extends \PHPUnit\Framework\TestCase
{
    public function testDefaultConfig()
    {
        $engine = new MockSyslogEngine();
        $this->assertEquals('', $engine->config('identity'));
        $this->assertEquals(LOG_ODELAY, $engine->config('option'));
        $this->assertEquals(LOG_USER, $engine->config('facility'));
        $this->assertEquals([], $engine->config('levels'));
        $this->assertEquals([], $engine->config('channels'));
    }
    public function testLog()
    {
        $engine = new MockSyslogEngine();
        $id = uniqid();
        $this->assertNull($engine->log('error', 'Error code {value}', ['value' => $id]));
        $date = date('Y-m-d G:i:s');
        $this->assertStringContainsString("3:[{$date}] application ERROR: Error code {$id}", $engine->written());
    }

    /**
     * Just test that tests are run
     *
     * @return void
     */
    public function testLogAddDebug()
    {
        $enigne = new SyslogEngine();
        $this->assertNull($enigne->log('debug', 'SyslogEngineTest run'));
    }
}
