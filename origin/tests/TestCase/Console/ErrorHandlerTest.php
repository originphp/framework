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
       
        $expected = '<redBackground> Exception </redBackground> <yellow>Something went wrong</yellow>\n\n<cyan>origin/tests/TestCase/Console/ErrorHandlerTest.php</cyan> <yellowBackground> 69 </yellowBackground>\n\n<blue>64</blue> <white>        throw new Exception(\'Something went wrong\');</white>\n<blue>65</blue> <white>    }</white>\n<blue>66</blue> <white>    public function testExceptionRender()</white>\n<blue>67</blue> <white>    {</white>\n<blue>68</blue> <white>        try {</white>\n<blue>69</blue> <redBackground>            throw new Exception(\'Something went wrong\');</redBackground>\n<blue>70</blue> <white>        } catch (Exception $ex) {</white>\n<blue>71</blue> <white>        }</white>\n<blue>72</blue> <white>        $ErrorHandler = new MockErrorHandler();</white>\n<blue>73</blue> <white>        $ErrorHandler->setUp();</white>\n<blue>74</blue> <white>        $ErrorHandler->exceptionHandler($ex);</white>\n\n<blueBackground> Stack Trace </blueBackground>\n\n<cyan>Exception </cyan><green></green>\n<white>origin/tests/TestCase/Console/ErrorHandlerTest.php</white> <yellowBackground> 69 </yellowBackground>\n\n<cyan>Origin\Test\Console\ErrorHandlerTest </cyan><green>testExceptionRender</green>\n<white>phar:///usr/local/bin/phpunit/phpunit/Framework/TestCase.php</white> <yellowBackground> 1153 </yellowBackground>\n\n<cyan>PHPUnit\Framework\TestCase </cyan><green>runTest</green>\n<white>phar:///usr/local/bin/phpunit/phpunit/Framework/TestCase.php</white> <yellowBackground> 842 </yellowBackground>\n\n<yellow>Use -backtrace to see the full backtrace.</yellow>\n\n';
        $this->assertEquals($expected, str_replace("\n", '\n', $message));
    }
    public function testConsoleOutput()
    {
        $ErrorHandler = new MockErrorHandler();
        $consoleOutput = $ErrorHandler->callMethod('consoleOutput');
        $this->assertInstanceOf(ConsoleOutput::class, $consoleOutput);
    }
}
