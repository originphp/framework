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

class MockShellDispatcher extends ShellDispatcher
{
    use TestTrait;
    protected $shellList = null;

    protected function getShellList()
    {
        if ($this->shellList === null) {
            $this->populateShelllist();
        }
        return $this->shellList;
    }
    public function setShellList(array $shellList)
    {
        $this->shellList = $shellList;
    }
    public function populateShelllist()
    {
        $this->shellList = parent::getShellList();
    }
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
        $ShellDispatcher = new MockShellDispatcher([], new AnotherConsoleOutput('php://memory'));
        $result = $ShellDispatcher->callMethod('getShellList');
        $this->assertArrayHasKey('App', $result);
        $this->assertArrayHasKey('Core', $result);
        $this->assertArrayHasKey('Make', $result);
    }

    public function testDispatchAndOut()
    {
        $ConsoleOutput =  new AnotherConsoleOutput('php://memory');
        $ShellDispatcher = new MockShellDispatcher(['pathTo/origin.php','Origin\Test\Console\LemonPie'], $ConsoleOutput);
        $ShellDispatcher->start();
        $buffer =  $ConsoleOutput->read();
        $this->assertContains('LemonPie Shell', $buffer);
        $this->assertContains('initialize called', $buffer);
        $this->assertContains('startup called', $buffer);
        $this->assertContains('shutdown called', $buffer);
    }

   
    public function testPluginDispatchPluginSearch()
    {
        // Test Plugin Search
        $ShellDispatcher = new MockShellDispatcher(
            ['pathTo/origin.php','make'],
            new AnotherConsoleOutput('php://memory')
        );
        $result = $ShellDispatcher->start();
        $this->assertInstanceOf(Shell::class, $result);
    }

    /**
     * Check that its locating shells in app folder, since there is not
     * one lets make it look as if file exists then it will throw a missing exception
     *
     * @return void
     */
    public function testPluginDispatchApp()
    {
        // Test Plugin Search
        $ShellDispatcher = new MockShellDispatcher(
            ['pathTo/origin.php','math'],
            new AnotherConsoleOutput('php://memory')
        );
        $ShellDispatcher->setShellList([
            'App' => ['math'],
            'Core' => []
        ]);
        
        $this->expectException(MissingShellException::class);
        $this->expectExceptionMessage('App\Console\MathShell'); #! Important
        $result = $ShellDispatcher->start();
    }

    public function testPluginDispatchCore()
    {
        // Test Plugin Search
        $ShellDispatcher = new MockShellDispatcher(
            ['pathTo/origin.php','schema'],
            new AnotherConsoleOutput('php://memory')
        );
        $result = $ShellDispatcher->start();
        $this->assertInstanceOf(Shell::class, $result);
    }

    public function testPluginDispatchPluginCall()
    {
        // Test direct plugin call
        $ShellDispatcher = new MockShellDispatcher(
             ['pathTo/origin.php','Make.make','help'],
             new AnotherConsoleOutput('php://memory')
         );
        $result = $ShellDispatcher->start();
      
        $this->assertInstanceOf(Shell::class, $result);
    }
    public function testInvalidShell()
    {
        $ShellDispatcher = new MockShellDispatcher(['pathTo/origin.php','NonExistantShellClass'], new AnotherConsoleOutput('php://memory'));
       
        $this->expectException(MissingShellException::class);
        $ShellDispatcher->start();
    }

    public function testInvalidArg()
    {
        $ShellDispatcher = new MockShellDispatcher(['pathTo/origin.php','Origin\Test\Console\LemonPie','unknowMethod'], new AnotherConsoleOutput('php://memory'));
       
        $this->expectException(MissingShellMethodException::class);
        $ShellDispatcher->start();
    }

    public function testInvalidPrivateMethod()
    {
        $ShellDispatcher = new MockShellDispatcher(['pathTo/origin.php','Origin\Test\Console\LemonPie','privateMethod'], new AnotherConsoleOutput('php://memory'));
       
        $this->expectException(MissingShellMethodException::class);
        $ShellDispatcher->start();
    }
}
