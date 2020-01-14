<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Test\Console\Command;

use Origin\Model\Model;
use Origin\Console\ConsoleIo;
use Origin\Model\ModelRegistry;
use Origin\TestSuite\TestTrait;
use Origin\Console\Command\Command;
use Origin\TestSuite\Stub\ConsoleOutput;
use Origin\Console\Exception\ConsoleException;
use Origin\Model\Exception\MissingModelException;
use Origin\Console\Exception\StopExecutionException;

class MockCommand extends Command
{
    use TestTrait;
    protected $name = 'mock-command';
    
    protected $description = ['Mock command'];

    public function execute() : void
    {
        $this->out('This is a mock command');
    }
}

class BackupCommand extends Command
{
    use TestTrait;
    protected $name = 'backup';
    
    protected $description = ['Backup command'];

    public function initialize() : void
    {
        $this->addOption('connection', ['short' => 'c','description' => 'Which datasource to use','default' => 'main']);
        $this->addArgument('database', ['required' => true,'description' => 'The database to backup']);
        $this->addArgument('filename', ['description' => 'The filename to output too']);
    }

    public function execute() : void
    {
        $msg = sprintf('The database %s was backedup from the %s datasource', $this->arguments('database'), $this->options('connection'));
        $this->out($msg);
    }
}

class CacheCommand extends Command
{
    use TestTrait;
    protected $name = 'cache';
    
    protected $description = ['This is a mock command'];

    public function initialize() : void
    {
        $this->addSubCommand('enable', ['description' => 'enables the cache']);
        $this->addSubCommand('disable', ['description' => 'disables the cache']);
    }

    public function execute() : void
    {
    }
    public function enable()
    {
        $this->out('Cache enabled');
    }
    public function disable()
    {
        $this->out('Cache disabled');
    }
}

class CommandTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->out = new ConsoleOutput();
        $this->out->mode(ConsoleOutput::RAW);
    }

    public function io()
    {
        return new ConsoleIo($this->out, $this->out);
    }
  
    public function testOutputError()
    {
        $consoleOutput = new ConsoleOutput();
        $consoleOutput->mode(ConsoleOutput::RAW);
        $io = new ConsoleIo($consoleOutput, $consoleOutput);
        $mock = $this->getMockBuilder(MockCommand::class)
            ->setConstructorArgs([$io])
            ->setMethods(['abort'])
            ->getMock();

        $mock->throwError('test', 'A comment about this error');
        $output = $consoleOutput->read();
        $this->assertStringContainsString('<exception> ERROR </exception> <heading>test</heading>', $output);
        $this->assertStringContainsString('A comment about this error', $output);
    }

    public function testName()
    {
        $command = new MockCommand();
        $command->name('hello');
        $this->assertEquals('hello', $command->name());
    }

    public function testDescription()
    {
        $command = new MockCommand();
        $command->description('A quick brown fox...');
        $this->assertEquals('A quick brown fox...', $command->description());
    }

    public function testArguments()
    {
        $command = new MockCommand();
        $command->setProperty('arguments', ['name' => 'foo']);
        $this->assertEquals('foo', $command->arguments('name'));
        $this->assertEquals(['name' => 'foo'], $command->arguments());
        $this->assertNull($command->arguments('got'));
    }
    public function testOptions()
    {
        $command = new MockCommand();
        $command->setProperty('options', ['name' => 'foo']);
        $this->assertEquals('foo', $command->options('name'));
        $this->assertEquals(['name' => 'foo'], $command->options());
        $this->assertNull($command->options('got'));
    }

    public function testValidateName()
    {
        $command = new MockCommand();
        $this->assertNull($command->callMethod('validateName', ['foo']));
        $this->assertNull($command->callMethod('validateName', ['foo-bar']));
        $this->assertNull($command->callMethod('validateName', ['foo:bar']));
        $this->assertNull($command->callMethod('validateName', ['foo-bar:bar-foo']));
        $this->expectException(ConsoleException::class);
        $command->callMethod('validateName', ['foo bar']);
    }

    public function testRun()
    {
        $command = new BackupCommand($this->io());
        $command->run(['my_database']);
        $this->assertStringContainsString('The database my_database was backedup from the main datasource', $this->out->read());
    }

    public function testRunArgumentParserError()
    {
        $command = new BackupCommand($this->io());
        $command->run([]);
        $this->assertStringContainsString('<exception> ERROR </exception> <text>Missing required argument `database`</text>', $this->out->read());
    }

    public function testRunHelp()
    {
        $command = new BackupCommand($this->io());
        $command->addUsage('backup mydb backup.zip');
        $command->run(['--help']);
        $this->assertStringContainsString('backup [options] database [filename]', $this->out->read());
        $this->assertStringContainsString('backup mydb backup.zip', $this->out->read());
    }

    public function testLoadModel()
    {
        $Post = new Model(['name' => 'Post','connection' => 'test']);
        ModelRegistry::set('Post', $Post);
        $command = new MockCommand();
        $this->assertInstanceOf(Model::class, $command->loadModel('Post'));
        $this->assertInstanceOf(Model::class, $command->Post);
        $this->assertInstanceOf(Model::class, $command->loadModel('Post')); // Test loading from property
        $this->ExpectException(MissingModelException::class);
        $command->loadModel('Foo');
    }

    public function testInfoWarningEtc()
    {
        $command = new MockCommand($this->io());
        $command->info(['Task was done']);
        $command->warning('It took too long');
        $command->notice('Took 10 seconds');
        $command->debug('This will not appear');
        $command->success('All good');
        $command->error('Some error');

        $expected = <<<EOF
<info>Task was done</info>
<warning>It took too long</warning>
<notice>Took 10 seconds</notice>
<success>All good</success>
<error>Some error</error>
EOF;

        $this->assertStringContainsString($expected, $this->out->read());
    }

    public function testInfo()
    {
        $command = new MockCommand($this->io());
        $command->info('some text no placeholder');
        $this->assertStringContainsString('some text no placeholder', $this->out->read());
        $command->info('User {id}', ['id' => 1234]);
        $this->assertStringContainsString('User 1234', $this->out->read());
    }

    public function testWarning()
    {
        $command = new MockCommand($this->io());
        $command->warning('some text no placeholder');
        $this->assertStringContainsString('some text no placeholder', $this->out->read());
        $command->warning('User {id}', ['id' => 1234]);
        $this->assertStringContainsString('User 1234', $this->out->read());
    }

    public function testError()
    {
        $command = new MockCommand($this->io());
        $command->error('some text no placeholder');
        $this->assertStringContainsString('some text no placeholder', $this->out->read());
        $command->error('User {id}', ['id' => 1234]);
        $this->assertStringContainsString('User 1234', $this->out->read());
    }
    
    public function testNotice()
    {
        $command = new MockCommand($this->io());
        $command->notice('some text no placeholder');
        $this->assertStringContainsString('some text no placeholder', $this->out->read());
        $command->notice('User {id}', ['id' => 1234]);
        $this->assertStringContainsString('User 1234', $this->out->read());
    }

    public function testSuccess()
    {
        $command = new MockCommand($this->io());
        $command->success('some text no placeholder');
        $this->assertStringContainsString('some text no placeholder', $this->out->read());
        $command->success('User {id}', ['id' => 1234]);
        $this->assertStringContainsString('User 1234', $this->out->read());
    }

    public function testDebugVerbose()
    {
        $command = new MockCommand($this->io());
        $command->debug('hbm24 = x411'); // Verbose disabled
        $this->assertStringNotContainsString('hbm24 = x411', $this->out->read());
        $command->run(['--verbose']);
        $command->debug('x345 = 1234'); // Verbose enabled
        $this->assertStringContainsString('<debug>x345 = 1234</debug>', $this->out->read());
    }

    public function testRunCommand()
    {
        $command = new MockCommand($this->io());
        $command->runCommand('cache:reset');
        $this->assertStringContainsString('Cache has been reset', $this->out->read());
    }

    public function testRunCommandDoesNotExist()
    {
        $this->expectException(ConsoleException::class);
        $command = new MockCommand($this->io());
        $command->runCommand('does-not-exist');
    }

    public function testRunCommandWithArgs()
    {
        $command = new MockCommand($this->io());
        $command->runCommand('say-hello', [
            '--color=blue',
            'jon',
        ]);
        $this->assertStringContainsString('<blue>Hello jon</blue>', $this->out->read());
    }

    public function testRunCommandWithArgs2()
    {
        $command = new MockCommand($this->io());
        $command->runCommand('say-hello', [
            '--color' => 'red',
            'jim',
        ]);
        $this->assertStringContainsString('<red>Hello jim</red>', $this->out->read());
    }

    public function testAbort()
    {
        $this->expectException(StopExecutionException::class);
        $command = new MockCommand($this->io());
        $command->abort();
    }
    
    public function testExit()
    {
        $this->expectException(StopExecutionException::class);
        $command = new MockCommand($this->io());
        $command->exit();
    }
}
