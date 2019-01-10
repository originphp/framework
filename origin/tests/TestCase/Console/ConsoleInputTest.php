<?php
/**
 * OriginPHP Framework
 * Copyright 2018 Jamiel Sharief.
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

use Origin\Test\Console;
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
        $ConsoleInput = new MockConsoleInput();
        
        fwrite(STDERR, 'Press Enter to continue');
        $this->assertEquals('', $ConsoleInput->read());

        fwrite(STDERR, 'Press q to continue');
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
}
