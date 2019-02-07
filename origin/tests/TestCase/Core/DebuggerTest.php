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

class DebuggerTest extends \PHPUnit\Framework\TestCase
{
    public function testBacktrace()
    {
        $debugger = new Debugger();
        $result = $debugger->backtrace();

        $expected = [
            'file' => 'phar:///usr/local/bin/phpunit/phpunit/Framework/TestCase.php',
            'line' => '1153',
            'class' => 'Origin\Test\Core\DebuggerTest',
            'function' => 'testBacktrace',
            'args' => []
        ];

        $this->assertArrayHasKey('namespace', $result);
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('stackFrames', $result);
        
        $this->assertEquals($expected, $result['stackFrames'][0]);
    }
}
