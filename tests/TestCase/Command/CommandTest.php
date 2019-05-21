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

namespace Origin\Test\Command;

use Origin\Console\ConsoleIo;
use Origin\TestSuite\Stub\ConsoleOutput;
use Origin\Command\Command;

class MockCommand extends Command
{
    protected $name = 'mock-command';
}
class MockConsoleIo extends ConsoleIo
{
    public function getContents()
    {
        return $this->stdin->read();
    }
}

class CommandTest extends \PHPUnit\Framework\TestCase
{
    public function getObject()
    {
        $consoleOutput = new ConsoleOutput('php://memory');
        $io = new ConsoleIo($consoleOutput, $consoleOutput);

        return new Command($io);
    }

    public function testOutputError()
    {
        $consoleOutput = new ConsoleOutput();
        $io = new ConsoleIo($consoleOutput, $consoleOutput);
        $mock = $this->getMockBuilder(MockCommand::class)
                        ->setConstructorArgs([$io])
                         ->setMethods(['abort'])
                         ->getMock();

        $mock->throwError('test', 'A comment about this error');
        $output = $consoleOutput->read();
        $this->assertContains('<exception> ERROR </exception> <heading>test</heading>', $output);
        $this->assertContains('A comment about this error', $output);
    }
}
