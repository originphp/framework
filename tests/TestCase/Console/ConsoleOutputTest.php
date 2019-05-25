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
        $stream =  $this->stream;
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
        $ConsoleOutput->write('<yellow>hello world</yellow>');
        $this->assertContains("\033[93mhello world\033[39m\n", $ConsoleOutput->read());
    }

    public function testStyles()
    {
        $ConsoleOutput = new ConsoleOutput();
         $this->assertEquals(['color' => 'white','background'=>'lightRed'], $ConsoleOutput->styles('exception'));
        
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
        $ConsoleOutput->styles('complete', ['background'=>'lightRed','underline'=>true,'color'=>'white']);
        $ConsoleOutput->write('<complete>Test</complete>');
        $this->assertEquals("\033[97;101;4mTest\033[39;49;24m\n", $ConsoleOutput->read());

        $ConsoleOutput = new ConsoleOutput();
        $ConsoleOutput->mode(ConsoleOutput::COLOR);
        $ConsoleOutput->write('<unkown>This is an unkown style</unkown>');
        $this->assertContains('<unkown>This is an unkown style</unkown>',$ConsoleOutput->read());

    }

    public function testNestedStyle(){
        $ConsoleOutput = new ConsoleOutput();
        $ConsoleOutput->mode(ConsoleOutput::COLOR);
        $ConsoleOutput->write('<text>This is a <yellow>test</yellow></text>');
        $this->assertEquals('494ad65c5fb334414d393d454f8d6d50',md5($ConsoleOutput->read()));
    }

    public function testModeException(){
        $ConsoleOutput = new ConsoleOutput();
        $this->expectException(InvalidArgumentException::class);
        $ConsoleOutput->mode(007);
    }

    public function testEmptySet(){
        $ConsoleOutput = new ConsoleOutput();
        $ConsoleOutput->mode(ConsoleOutput::COLOR);
        $ConsoleOutput->styles('foo', ['foo'=>'bar']);
        $ConsoleOutput->write('<foo>bar</foo>');
        $this->assertEquals("bar\n",$ConsoleOutput->read());
    }
}
