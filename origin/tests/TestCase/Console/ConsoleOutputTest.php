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

namespace Origin\Test\Console;

use Origin\Console\ConsoleOutput;

class MockConsoleOutput extends ConsoleOutput
{
    public function getStream()
    {
        return $this->stream;
    }
    public function getStyles()
    {
        return $this->styles;
    }
}

class ConsoleOutputTest extends \PHPUnit\Framework\TestCase
{
    public function testConstruct()
    {
        $ConsoleOutput = new MockConsoleOutput();
        $this->assertTrue(is_resource($ConsoleOutput->getStream()));
    }
    public function testWrite()
    {
        $ConsoleOutput = new MockConsoleOutput('php://memory');
        $ConsoleOutput->write('hello world');
        $stream = $ConsoleOutput->getStream();
        rewind($stream);
        $this->assertEquals('hello world', stream_get_contents($stream));
    }

    public function testTags()
    {
        $ConsoleOutput = new MockConsoleOutput('php://memory');
        $ConsoleOutput->write('<unkown>hello world</unkown>');
        $stream = $ConsoleOutput->getStream();
        rewind($stream);
        $this->assertEquals('<unkown>hello world</unkown>', stream_get_contents($stream));

        $ConsoleOutput = new MockConsoleOutput('php://memory');
        $ConsoleOutput->write('<yellow>hello world</yellow>');
        $stream = $ConsoleOutput->getStream();
        rewind($stream);
        $this->assertEquals("\033[33mhello world\033[0m", stream_get_contents($stream));

        $ConsoleOutput = new MockConsoleOutput('php://memory');
        $ConsoleOutput->write('<primary>hello world</primary>');
        $stream = $ConsoleOutput->getStream();
        rewind($stream);
        $this->assertEquals("\033[97;44;1mhello world\033[0m", stream_get_contents($stream));
    }

    public function testStyles()
    {
        $ConsoleOutput = new MockConsoleOutput('php://memory');
        $this->assertEquals($ConsoleOutput->getStyles(), $ConsoleOutput->styles());
        $this->assertEquals(array('text' => 'white','background'=>'blue','bold' => true), $ConsoleOutput->styles('primary'));
        
        $ConsoleOutput->styles('foo', ['bar']);
        $this->assertEquals(['bar'], $ConsoleOutput->styles('foo'));
    }
}
