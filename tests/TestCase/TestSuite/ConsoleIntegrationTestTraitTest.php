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

namespace Origin\Test\TestSuite;

use Origin\TestSuite\TestTrait;
use Origin\Console\Command\Command;
use Origin\TestSuite\ConsoleIntegrationTestTrait;

class ConsoleIntegrationTestTraitTest extends \PHPUnit\Framework\TestCase
{
    use ConsoleIntegrationTestTrait;
    use TestTrait;

    public function testExec()
    {
        $this->exec('test say');
        $this->assertOutputContains('Hello world!');
        $this->assertOutputRegExp('/WORLD/i');
        $this->assertExitSuccess();
        $this->assertExitCode(0);
    }

    public function testOutput()
    {
        $this->exec('test say');
        $this->assertStringContainsString('Hello world!', $this->output());
    }

    public function testExecEnclosed()
    {
        $this->exec('test say --message="foo bar"');
        $this->assertStringContainsString('foo bar', $this->output());
    }

    public function testExecInput()
    {
        $this->exec('test ask', ['no']);
        $this->assertOutputContains('You entered no');
        $this->assertOutputNotContains('foo');
        $this->assertExitSuccess();
    }

    public function testExecError()
    {
        $this->exec('test omg');
        $this->assertExitError();
        $this->assertErrorContains('OMG! Its all Gone pete tong');
        $this->assertErrorRegExp('/PETE/i');
        $this->assertErrorNotContains('Tony DeVit');
        $this->assertExitCode(1);
    }

    public function testError()
    {
        $this->exec('test omg');
        $this->assertStringContainsString('OMG! Its all Gone pete tong', $this->error());
    }

    public function testEmpty()
    {
        $this->exec('test empty');
        $this->assertOutputEmpty();
    }

    public function testCommand()
    {
        $this->exec('test say');
        $this->assertInstanceOf(Command::class, $this->command());
    }

    public function testCommandUnkown()
    {
        $this->exec('this-does-not-exist');
        $this->assertNull($this->command());
    }
}
