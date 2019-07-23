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

use Origin\Core\Debugger;
use Origin\TestSuite\TestTrait;

class MockDebugger extends Debugger
{
    use TestTrait;
}

class DebuggerTest extends \PHPUnit\Framework\TestCase
{
    public function testBacktrace()
    {
        $debugger = new Debugger();
        $result = $debugger->backtrace();

        $expected = [
            'file' => 'phar:///usr/local/bin/phpunit/phpunit/Framework/TestCase.php',
            'line' => '1154',
            'class' => 'Origin\Test\Core\DebuggerTest',
            'function' => 'testBacktrace',
            'args' => [],
        ];

        $this->assertArrayHasKey('namespace', $result);
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('stackFrames', $result);
        $frame = $result['stackFrames'][0];
        $this->assertContains('/Framework/TestCase.php', $frame['file']);
        $this->assertNotNull($frame['line']); // This changes between systems or versions
        $this->assertEquals('Origin\Test\Core\DebuggerTest', $frame['class']);
        $this->assertEquals('testBacktrace', $frame['function']);
        $this->assertEquals([], $frame['args']);
    }
    public function testNamespaceSplit()
    {
        $debugger = new MockDebugger();
        list($namespace, $classname) = $debugger->callMethod('namespaceSplit', ['Origin\Framework\Dispatcher']);
        $this->assertEquals('Origin\Framework', $namespace);
        $this->assertEquals('Dispatcher', $classname);
        list($namespace, $classname) = $debugger->callMethod('namespaceSplit', ['NoNamespace']);
       
        $this->assertNull($namespace);
        $this->assertEquals('NoNamespace', $classname);
    }
}
