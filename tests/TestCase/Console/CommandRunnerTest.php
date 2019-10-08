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

use Origin\Console\ConsoleIo;
use Origin\Console\CommandRunner;
use Origin\TestSuite\Stub\ConsoleOutput;
use App\Console\Command\CacheResetCommand;
use Origin\Console\Command\DbCreateCommand;
use App\Console\Command\SaySomethingCommand;

class MockCommandRunner extends CommandRunner
{
    public function io()
    {
        return $this->io;
    }
}
class CommandRunnerTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->out = new ConsoleOutput();
        $this->out->mode(ConsoleOutput::RAW);
    }

    public function commandRunner()
    {
        return new CommandRunner(new ConsoleIo($this->out, $this->out));
    }

    public function testConstructor()
    {
        $runner = new MockCommandRunner();
        $this->assertInstanceOf(ConsoleIo::class, $runner->io());
    }
    
    public function testRunNoArgs()
    {
        $runner = $this->commandRunner();
        $runner->run([]);
        $this->assertEquals('e0a9209ed863fb7d1e1e0e1ae59c2f35', md5($this->out->read())); // rest
    }

    public function testFindCommand()
    {
        $result = $this->commandRunner()->findCommand('say-hello');
        $this->assertInstanceOf(SaySomethingCommand::class, $result);
        $result = $this->commandRunner()->findCommand('db:create'); // standard name
        $this->assertInstanceOf(DbCreateCommand::class, $result);
        $result = $this->commandRunner()->findCommand('cache:reset'); // standard name + in app folder
        $this->assertInstanceOf(CacheResetCommand::class, $result);
        $this->assertNull($this->commandRunner()->findCommand('purple-disco-machine:player'));
    }

    public function testRun()
    {
        $result = $this->commandRunner()->run([
            '/vendor/somescript.php',
            'say-hello',
            '--color=blue',
            'jim',
        ]);

        $this->assertTrue($result);
        $this->assertStringContainsString('<blue>Hello jim</blue>', $this->out->read());
    }

    public function testRunUnkownCommand()
    {
        $this->assertFalse($this->commandRunner()->run([
            '/vendor/somescript.php',
            'purple-disco-machine:player',
        ]));
    }
    /**
     * throw a Duplicate database: 7 ERROR:  database "origin" already exists
     *
     * @return void
     */
    public function testRunError()
    {
        $result = $this->commandRunner()->run([
            '/path-to-script/script.php',
            'db:create',
        ]);
        $this->assertFalse($result);
    }
}
