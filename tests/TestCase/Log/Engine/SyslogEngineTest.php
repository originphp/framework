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
    public $written = null;
    protected function openlog(string $identity, int $option = null, int $facility = null) :bool
    {
        return true;
    }
    protected function write(int $priority, string $message) :bool
    {
        $this->written = $priority . ':' . $message;

        return true;
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
        $this->assertTrue($engine->log('error', 'Error code {value}', ['value' => $id]));
        $date = date('Y-m-d G:i:s');
        $this->assertContains("3:[{$date}] application ERROR: Error code {$id}", $engine->written);
    }

    /**
     * Just test that tests are run
     *
     * @return void
     */
    public function testLogAddDebug()
    {
        $enigne = new SyslogEngine();
        $this->assertTrue($enigne->log('debug', 'SyslogEngineTest run'));
    }
}
