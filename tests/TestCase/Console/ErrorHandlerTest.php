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

use Origin\Console\ErrorHandler;
use Origin\Exception\Exception;
use Origin\TestSuite\TestTrait;
use Origin\Console\ConsoleOutput;

class MockErrorHandler extends ErrorHandler
{
    use TestTrait;
    public function setUp()
    {
        $this->consoleOutput = new MockConsoleOutputErrorHandler();
    }
    public function read()
    {
        return $this->consoleOutput->read();
    }
}
class MockConsoleOutputErrorHandler
{
    protected $buffer = '';

    public function read()
    {
        return $this->buffer;
    }
    public function write(string $data)
    {
        $this->buffer .= $data;
    }
}
class ErrorHandlerTest extends \PHPUnit\Framework\TestCase
{
    public function testRegisterError()
    {
        $ErrorHandler = new MockErrorHandler();
        $ErrorHandler->register();
        $this->expectException(\ErrorException::class);
            
        $original = unserialize('string');
        restore_error_handler(); // Don't think
    }
    public function testRegisterException()
    {
        $ErrorHandler = new MockErrorHandler();
        $ErrorHandler->register();
        $ErrorHandler->setUp();
        $this->expectException(Exception::class);
        throw new Exception('Something went wrong');
    }
    public function testExceptionRender()
    {
        try {
            throw new Exception('Something went wrong');
        } catch (Exception $ex) {
        }
        $ErrorHandler = new MockErrorHandler();
        $ErrorHandler->setUp();
        $ErrorHandler->exceptionHandler($ex);
        $message = $ErrorHandler->read();

        /**
         * @todo On different systems line numbers etc are different ie. phpunit etc
         */
        $this->assertContains('<redBackground> Exception </redBackground> <yellow>Something went wrong</yellow>',$message);
 
        $this->assertContains('<yellowBackground> 69 </yellowBackground>',$message);
        $this->assertContains('<blue>64</blue>',$message);
        $this->assertContains('<cyan>Exception </cyan><green></green><white>tests/TestCase/Console/ErrorHandlerTest.php</white>',$message);
        $this->assertContains('<cyan>Origin\Test\Console\ErrorHandlerTest </cyan><green>testExceptionRender</green>',$message);
        $this->assertContains('<redBackground>            throw new Exception(\'Something went wrong\');</redBackground',$message);
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
        $ErrorHandler->setUp();
        @unlink('someFileThatDoesNotExist'); //
        $this->assertTrue(true); // if we get here all is ok!
    }
}
