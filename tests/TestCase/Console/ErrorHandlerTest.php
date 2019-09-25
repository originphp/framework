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

use Origin\Exception\Exception;
use Origin\TestSuite\TestTrait;
use Origin\Console\ErrorHandler;
use Origin\Console\ConsoleOutput;

//use Origin\TestSuite\Stub\ConsoleOutput as MockConsoleOutputErrorHandler;

class MockErrorHandler extends ErrorHandler
{
    use TestTrait;
    public function setup()
    {
        $this->consoleOutput = new MockConsoleOutputErrorHandler();
    }
    public function read()
    {
        return $this->consoleOutput->read();
    }
}

class MockConsoleOutputErrorHandler extends ConsoleOutput
{
    protected $buffer = '';

    public function read()
    {
        return $this->buffer;
    }
    public function write($data, $newLine = true) : int
    {
        $this->buffer .= $data;

        return strlen($data);
    }
}

class ErrorHandlerTest extends \PHPUnit\Framework\TestCase
{
    public function testExceptionRender()
    {
        try {
            throw new Exception('Something went wrong');
        } catch (Exception $ex) {
        }
        $ErrorHandler = new MockErrorHandler();
        $ErrorHandler->setup();
        $ErrorHandler->exceptionHandler($ex);
        $message = $ErrorHandler->read();
  
        /**
         * @internal On different systems line numbers etc are different ie. phpunit etc
         */
        $this->assertStringContainsString('<redBackground> Exception </redBackground> <yellow>Something went wrong</yellow>', $message);
 
        $this->assertStringContainsString('<yellowBackground> 58 </yellowBackground>', $message);
        $this->assertStringContainsString('<blue>54</blue>', $message);
        $this->assertStringContainsString('<cyan>Exception </cyan><green></green><white>tests/TestCase/Console/ErrorHandlerTest.php</white>', $message);
        $this->assertStringContainsString('<cyan>Origin\Test\Console\ErrorHandlerTest </cyan><green>testExceptionRender</green>', $message);
        $this->assertStringContainsString('<redBackground>            throw new Exception(\'Something went wrong\');</redBackground', $message);
    }
    public function testConsoleOutput()
    {
        $ErrorHandler = new MockErrorHandler();
        $consoleOutput = $ErrorHandler->callMethod('consoleOutput');
        $this->assertInstanceOf(ConsoleOutput::class, $consoleOutput);
    }
    public function testSupression()
    {
        $ErrorHandler = new MockErrorHandler();
        $ErrorHandler->register();
        $ErrorHandler->setup();
        @unlink('someFileThatDoesNotExist'); //
        $this->assertTrue(true); // if we get here all is ok!
    }
}
