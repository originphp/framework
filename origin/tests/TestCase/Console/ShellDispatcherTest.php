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

use Origin\Console\ShellDispatcher;
use Origin\Console\ConsoleOutput;
use Origin\TestSuite\TestTrait;
use Origin\Console\Shell;
use Origin\Console\Exception\MissingShellException;
use Origin\Console\Exception\MissingShellMethodException;

class MockShelLDispatcher extends ShellDispatcher
{
    use TestTrait;
}

class LemonPieShell extends Shell
{
    public function main()
    {
        $this->out('LemonPie Shell');
    }

    private function privateMethod()
    {
        return 'secret';
    }

    public function initialize(array $config)
    {
        $this->out('initialize called');
    }
    public function startup()
    {
        $this->out('startup called');
    }
    public function shutdown()
    {
        $this->out('shutdown called');
    }
}

class AnotherConsoleOutput extends ConsoleOutput
{
    public function read()
    {
        $stream = $this->stream;
        rewind($stream);
        return stream_get_contents($stream);
    }
}

class ShellDispatcherTest extends \PHPUnit\Framework\TestCase
{

    /**
     * Create the Shell Object, and display basic
     */
    public function testNewDispatcher()
    {
        $ConsoleOutput =  new AnotherConsoleOutput('php://memory');
        $ShellDispatcher = new ShellDispatcher([], $ConsoleOutput);
        $this->assertFalse($ShellDispatcher->start());
        $this->assertContains('OriginPHP Console', $ConsoleOutput->read());
    }

    public function testGetShellList()
    {
        $ShellDispatcher = new MockShelLDispatcher([], new AnotherConsoleOutput('php://memory'));
        $result = $ShellDispatcher->callMethod('getShellList');
        $this->assertArrayHasKey('App', $result);
        $this->assertArrayHasKey('Core', $result);
        $this->assertArrayHasKey('Debug', $result);
        $this->assertArrayHasKey('Make', $result);
    }

    public function testDispatchAndOut()
    {
        $ConsoleOutput =  new AnotherConsoleOutput('php://memory');
        $ShellDispatcher = new MockShelLDispatcher(['pathTo/origin.php','Origin\Test\Console\LemonPie'], $ConsoleOutput);
        $ShellDispatcher->start();
        $buffer =  $ConsoleOutput->read();
        $this->assertContains('LemonPie Shell', $buffer);
        $this->assertContains('initialize called', $buffer);
        $this->assertContains('startup called', $buffer);
        $this->assertContains('shutdown called', $buffer);
    }

    public function testInvalidArg()
    {
        $ShellDispatcher = new MockShelLDispatcher(['pathTo/origin.php','Origin\Test\Console\LemonPie','unknowMethod'], new AnotherConsoleOutput('php://memory'));
       
        $this->expectException(MissingShellMethodException::class);
        $ShellDispatcher->start();
    }

    public function testInvalidPrivateMethod()
    {
        $ShellDispatcher = new MockShelLDispatcher(['pathTo/origin.php','Origin\Test\Console\LemonPie','privateMethod'], new AnotherConsoleOutput('php://memory'));
       
        $this->expectException(MissingShellMethodException::class);
        $ShellDispatcher->start();
    }
}
