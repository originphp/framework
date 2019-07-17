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

use Origin\Console\ConsoleApplication;
use Origin\TestSuite\Stub\ConsoleOutput;
use Origin\Console\ConsoleIo;
use Origin\Command\Command;
use Origin\Console\Exception\ConsoleException;
use Origin\Exception\InvalidArgumentException;

class CacheEnableCommand extends Command
{
    protected $name = 'cache:enable';
    protected $description = 'Enables cache';
    public function execute()
    {
        $this->out('OK Cache enabled');
    }
}

class CacheDisableCommand extends Command
{
    protected $name = 'cache:disable';
    protected $description = 'Disables cache';

    public function execute()
    {
        $this->out('OK Cache disabled');
    }
}

class CacheDeleteCommand extends Command
{
    protected $name = 'cache:delete';
    protected $description = 'Deletes an item from the cache';

    public function initialize()
    {
        $this->addArgument('key', ['required'=>true]);
    }

    public function execute()
    {
        $key = $this->arguments('key');
        $this->out("{$key} deleted");
    }
}

class FooCommand extends Command
{
    public function execute()
    {
        $this->abort();
    }
}


class ConsoleApplicationTest extends \PHPUnit\Framework\TestCase
{
    protected function consoleApplication()
    {
        $this->output = new ConsoleOutput();
        $this->output->mode(ConsoleOutput::RAW);
        $io = new ConsoleIo($this->output, $this->output);
        return new ConsoleApplication($io);
    }

    public function testSettersGetters()
    {
        $app = new ConsoleApplication();
        $app->name('console-app');
        $app->description(['This is the description']);
        $this->assertEquals('console-app', $app->name());
        $this->assertContains('This is the description', $app->description());
    }

    public function testInvalidName()
    {
        $app = new ConsoleApplication();
        $this->expectException(ConsoleException::class);
        $app->name('abc 123');
    }

    public function testNoCommandsException()
    {
        $app = new ConsoleApplication();
        $app->name('no-command-exception');
        $this->expectException(ConsoleException::class);
        $app->run();
    }

    public function testApp()
    {
        $consoleApplication = $this->consoleApplication();
        $consoleApplication->name('cache');
        $consoleApplication->description(['Cache enabler and disabler']);
        $consoleApplication->addCommand('enable', CacheEnableCommand::class);
        $consoleApplication->addCommand('disable', CacheDisableCommand::class);
        $this->assertTrue($consoleApplication->run([]));
        $this->assertEquals('1c8e93d50325319c00b0440f3a9dd134', md5($this->output->read()));
    }

    public function testAppStopExcution()
    {
        $consoleApplication = $this->consoleApplication();
        $consoleApplication->addCommand('single', FooCommand::class);
        $this->assertFalse($consoleApplication->run());
    }

    public function testSingleCommandApp()
    {
        $consoleApplication = $this->consoleApplication();
        $consoleApplication->name('cache');
        $consoleApplication->addCommand('enable', CacheEnableCommand::class);
        $this->assertTrue($consoleApplication->run([]));
        $this->assertContains('OK Cache enabled', $this->output->read());
    }

    public function testArgumentParserError()
    {
        $consoleApplication = $this->consoleApplication();
        $consoleApplication->name('cache');
        $consoleApplication->addCommand('enable', CacheEnableCommand::class);
        $consoleApplication->addCommand('disable', CacheDisableCommand::class);
        $consoleApplication->addCommand('delete', CacheDeleteCommand::class);
        
        
        $this->assertFalse($consoleApplication->run(['delete']));

        $this->assertContains('Missing required argument `key`', $this->output->read());
    }

    public function testAppCommand()
    {
        $consoleApplication = $this->consoleApplication();
        $consoleApplication->name('cache');
        $consoleApplication->addCommand('enable', CacheEnableCommand::class);
        $consoleApplication->addCommand('disable', CacheDisableCommand::class);
        $this->assertTrue($consoleApplication->run(['enable']));

        $this->assertContains('OK Cache enabled', $this->output->read());

        $consoleApplication = $this->consoleApplication();
        $consoleApplication->name('cache');
        $consoleApplication->addCommand('enable', CacheEnableCommand::class);
        $consoleApplication->addCommand('disable', CacheDisableCommand::class);
        $consoleApplication->run(['disable']);
        $this->assertContains('OK Cache disabled', $this->output->read());

        $consoleApplication = $this->consoleApplication();
        $consoleApplication->name('cache');
        $consoleApplication->addCommand('enable', CacheEnableCommand::class);
        $consoleApplication->addCommand('disable', CacheDisableCommand::class);
        $this->assertFalse($consoleApplication->run(['reset']));
        $this->assertContains('Invalid command reset', $this->output->read());
    }

    public function testAddCommandException()
    {
        $consoleApplication = $this->consoleApplication();

        $this->expectException(ConsoleException::class);
        $consoleApplication->addCommand('foo rider', 'DoesNotReallyMatter');
    }

    public function testAddCommandInvalidArgument()
    {
        $consoleApplication = $this->consoleApplication();

        $this->expectException(InvalidArgumentException::class);
        $consoleApplication->addCommand('foo', 'DoesNotReallyMatter');
    }
}
