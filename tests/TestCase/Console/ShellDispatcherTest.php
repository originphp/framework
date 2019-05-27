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
use Origin\TestSuite\Stub\ConsoleOutput;
use Origin\Console\ConsoleInput;
use Origin\TestSuite\TestTrait;
use Origin\Console\Shell;
use Origin\Console\Exception\MissingShellException;

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
    private function privateMethod()
    {
        return 'secret';
    }

    public function start()
    {
    }
    public function initialize()
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


class ShellDispatcherTest extends \PHPUnit\Framework\TestCase
{

    /**
     * Create the Shell Object, and display basic
     */
    public function testNewDispatcher()
    {
        $ConsoleOutput =  new ConsoleOutput();
        $ShellDispatcher = new ShellDispatcher([], $ConsoleOutput, new ConsoleInput());
        $this->assertFalse($ShellDispatcher->start());
        $this->assertContains('OriginPHP', $ConsoleOutput->read());
    }

    public function testGetShellList()
    {
        $ShellDispatcher = new MockShellDispatcher([], new ConsoleOutput(), new ConsoleInput());
        $result = $ShellDispatcher->callMethod('getShellList');
        $this->assertArrayHasKey('App', $result);
        $this->assertArrayHasKey('Core', $result);
        $this->assertArrayHasKey('Make', $result);
    }

    public function testDispatchAndOut()
    {
        $ConsoleOutput =  new ConsoleOutput();
        $ShellDispatcher = new MockShellDispatcher(['pathTo/origin.php','Origin\Test\Console\LemonPie','start'], $ConsoleOutput, new ConsoleInput());
        $ShellDispatcher->start();
        $buffer =  $ConsoleOutput->read();
        
        $this->assertContains('initialize called', $buffer);
        $this->assertContains('startup called', $buffer);
        $this->assertContains('shutdown called', $buffer);
    }

    public function testPluginDispatchPluginSearch()
    {
        // Test Plugin Search
        $ShellDispatcher = new MockShellDispatcher(
            ['pathTo/origin.php','make','main'],
            new ConsoleOutput(),
            new ConsoleInput()
        );
       
        
        $this->assertTrue($ShellDispatcher->start());
        $this->assertInstanceOf(Shell::class, $ShellDispatcher->shell());
    }

    /**
     * Check that its locating shells in app folder, since there is not
     * one lets make it look as if file exists then it will throw a missing exception
     *
     * @return void
     */
    public function testDispatchApp()
    {
        // Test Plugin Search
        $ShellDispatcher = new MockShellDispatcher(
            ['pathTo/origin.php','math'],
            new ConsoleOutput(),
            new ConsoleInput()
        );
        $ShellDispatcher->setShellList([
            'App' => ['math'],
            'Core' => []
        ]);
        
        $this->expectException(MissingShellException::class);
        $this->expectExceptionMessage('App\Console\MathShell'); #! Important
        $result = $ShellDispatcher->start();
    }

    public function testDispatchCore()
    {
        $this->markTestSkipped('This has been removed and this feature is being depreciated');
        // Test Plugin Search
        $ShellDispatcher = new MockShellDispatcher(
            ['pathTo/origin.php','shell'],
            new ConsoleOutput(),
            new ConsoleInput()
        );

        $this->assertTrue($ShellDispatcher->start());
        $this->assertInstanceOf(Shell::class, $ShellDispatcher->shell());
    }

    public function testPluginDispatchPluginCall()
    {
        // Test direct plugin call
        $ShellDispatcher = new MockShellDispatcher(
             ['pathTo/origin.php','Make.make','main'],
             new ConsoleOutput(),
             new ConsoleInput()
         );
  
        $this->assertTrue($ShellDispatcher->start());
        $this->assertInstanceOf(Shell::class, $ShellDispatcher->shell());
    }
    public function testInvalidShell()
    {
        $ShellDispatcher = new MockShellDispatcher(
            [
            'pathTo/origin.php','NonExistantShellClass'],
             new ConsoleOutput(),
             new ConsoleInput()
        );
       
        $this->expectException(MissingShellException::class);
        $ShellDispatcher->start();
    }
}