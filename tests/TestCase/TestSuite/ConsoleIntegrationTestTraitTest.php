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

namespace Origin\Test\TestSuite;

use Origin\TestSuite\ConsoleIntegrationTestTrait;
use Origin\TestSuite\TestTrait;

class ConsoleIntegrationTestTraitTest extends \PHPUnit\Framework\TestCase
{
    use ConsoleIntegrationTestTrait;
    use TestTrait;

    public function testExec()
    {
        $this->exec('test say');
        $this->assertOutputContains('Hello world!');
        $this->assertExitSuccess();
    }

    public function testExecInput()
    {
        $this->exec('test ask', ['no']);
        $this->assertOutputContains('You entered no');
        $this->assertExitSuccess();
    }

    public function testExecError()
    {
        $this->exec('test omg');
        $this->assertExitError();
        $this->assertErrorContains('OMG! Its all Gone pete tong');
    }

    public function testEmpty()
    {
        $this->exec('test empty');
        $this->assertOutputEmpty();
    }
}
