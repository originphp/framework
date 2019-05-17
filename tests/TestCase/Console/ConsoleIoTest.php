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

namespace Origin\Test\Console\ConsoleIo;

use Origin\Console\ConsoleIo;
use Origin\Console\ConsoleOutput;

class MockConsoleOutput extends ConsoleOutput
{
    protected $data = '';
    public function read()
    {
        return $this->data;
    }

    public function write(string $data)
    {
        $this->data .= $data;
    }
}

class MockConsoleIo extends ConsoleIo
{
    public function getContents(){
        return $this->stdin->read();
    }
}

class ConsoleIoTest extends \PHPUnit\Framework\TestCase
{

    public function getConsoleIo(){
        $consoleOutput = new ConsoleOutput('php://memory');
        return new ConsoleIo( $consoleOutput, $consoleOutput);
    }
   

    public function testOutputError()
    {
       
        $io = $this->getConsoleIo();
    
        $io->error('test', 'A comment about this error');
        $output = $io->getContents();
        $this->assertContains('<exception> ERROR </exception> <heading>test</heading>', $output);
        $this->assertContains('A comment about this error', $output);
    }
}
