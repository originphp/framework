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

namespace Origin\Test\Console\ConsoleOutput;

use Origin\TestSuite\Stub\ConsoleOutput;
use Origin\Exception\InvalidArgumentException;

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
    public function clearStyles()
    {
        $this->styles = [];
    }
    public function getContents()
    {
        $stream = $this->stream;
        rewind($stream);

        return  stream_get_contents($stream);
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
        $ConsoleOutput = new ConsoleOutput();
        $ConsoleOutput->write('hello world');
        $this->assertEquals("hello world\n", $ConsoleOutput->read());
    }

    public function testTags()
    {
        $ConsoleOutput = new ConsoleOutput();
        $ConsoleOutput->write('<unkown>hello world</unkown>');
        $this->assertEquals("<unkown>hello world</unkown>\n", $ConsoleOutput->read());

        $ConsoleOutput = new ConsoleOutput();
        $ConsoleOutput->mode(ConsoleOutput::COLOR);
        $output = $ConsoleOutput->styleText('<yellow>hello world</yellow>');
    
        $this->assertContains("\033[93mhello world\033[39m", $output);
    }

    public function testStyles()
    {
        $ConsoleOutput = new ConsoleOutput();
        $this->assertEquals(['color' => 'white','background' => 'lightRed'], $ConsoleOutput->styles('exception'));
        
        $ConsoleOutput->styles('foo', ['bar']);
        $this->assertEquals(['bar'], $ConsoleOutput->styles('foo'));
        $this->assertNull($ConsoleOutput->styles('nonExistant'));
        $ConsoleOutput->styles('foo', false);
        $this->assertNull($ConsoleOutput->styles('foo'));
    }

    public function testOutput()
    {
        $ConsoleOutput = new ConsoleOutput();
        $ConsoleOutput->mode(ConsoleOutput::COLOR);
        $ConsoleOutput->styles('complete', ['background' => 'lightRed','underline' => true,'color' => 'white']);
        $output = $ConsoleOutput->styleText('<complete>Test</complete>');
        $this->assertEquals("\033[97;101;4mTest\033[39;49;24m", $output);

        $output = $ConsoleOutput->styleText('<unkown>This is an unkown style</unkown>');
        $this->assertContains('<unkown>This is an unkown style</unkown>', $output);
    }

    public function testNestedStyle()
    {
        $ConsoleOutput = new ConsoleOutput();
        $ConsoleOutput->mode(ConsoleOutput::COLOR);
        $result = $ConsoleOutput->styleText('<text>This is a <yellow>test</yellow></text>');
        $this->assertEquals('4bec28364108c365d9bb6b11bf15e69c', md5($result));
    }

    public function testModeException()
    {
        $ConsoleOutput = new ConsoleOutput();
        $mode = $ConsoleOutput->mode();
        $this->expectException(InvalidArgumentException::class);
        $ConsoleOutput->mode($mode + 1000);
    }

    public function testEmptySet()
    {
        $ConsoleOutput = new ConsoleOutput();
        $ConsoleOutput->mode(ConsoleOutput::COLOR);
        $ConsoleOutput->styles('foo', ['foo' => 'bar']);
        
        $result = $ConsoleOutput->styleText('<foo>bar</foo>');
        $this->assertEquals('bar', $result);
    }

    /**
     * This tests the amount of bytes, then goes back so you dont notice
     *
     * @return void
     */
    public function testBytes()
    {
        $ConsoleOutput = new \Origin\Console\ConsoleOutput();
        $bytes = $ConsoleOutput->write('OriginPHP', false);
        $this->assertEquals(9, $bytes);
        $ConsoleOutput->write(str_repeat("\x08", 9), false);
    }

    public function testWritePlain()
    {
        $ConsoleOutput = new ConsoleOutput();
        $ConsoleOutput->mode(ConsoleOutput::PLAIN);

        $ConsoleOutput->styles('foo', ['foo' => 'bar']);
        $result = $ConsoleOutput->styleText('<foo>bar</foo>');
        $this->assertEquals('bar', $result);
    }
}
