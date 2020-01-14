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

namespace Origin\Test\Console;

use Origin\Console\ConsoleInput;

class MockConsoleInput extends ConsoleInput
{
    public function getStream()
    {
        return $this->stream;
    }
}

class ConsoleInputTest extends \PHPUnit\Framework\TestCase
{
    public function testConstruct()
    {
        $ConsoleInput = new MockConsoleInput();
        $this->assertTrue(is_resource($ConsoleInput->getStream()));
    }
    
    public function testRead()
    {
        $ConsoleInput = $this->getMockBuilder(ConsoleInput::class)
            ->disableOriginalConstructor()
            ->setMethods(['read'])
            ->getMock();

        $ConsoleInput->expects($this->at(0))->method('read')->will($this->returnValue(''));
        $ConsoleInput->expects($this->at(1))->method('read')->will($this->returnValue('q'));

        $this->assertEquals('', $ConsoleInput->read());
        $this->assertEquals('q', $ConsoleInput->read());
    }

    /**
     * @depends testConstruct
     *
     * @return void
     */
    public function close()
    {
        $ConsoleInput = new MockConsoleInput();
        $this->assertTrue(is_resource($ConsoleInput->getStream()));
        $ConsoleInput->close();
        $this->assertFalse(is_resource($ConsoleInput->getStream()));
    }

    public function testReadAgain()
    {
        $name = uniqid();
        file_put_contents('/tmp/'. $name, 'yes');
        $consoleInput = new MockConsoleInput('file:///tmp/'. $name);
        $this->assertEquals('yes', $consoleInput->read());
    }
}
