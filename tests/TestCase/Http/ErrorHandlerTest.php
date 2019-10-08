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

namespace Origin\Test\Http;

use Origin\Log\Log;
use Origin\Core\Config;
use Origin\Http\Router;
use Origin\Http\Request;
use Origin\Http\ErrorHandler;
use Origin\Http\Exception\NotFoundException;
use Origin\Http\Exception\InternalErrorException;

class MockErrorHandler extends ErrorHandler
{
    /**
     * @var string
     */
    protected $response = null;
    protected $statusCode = null;

    public function sendResponse(string $response = null, int $statusCode = 200) : void
    {
        $this->response = $response;
        $this->statusCode = $statusCode;
    }

    // stub
    protected function cleanBuffer() : void
    {
    }

    // stub
    public function stop() : void
    {
    }

    public function response() : string
    {
        return $this->response;
    }
}
class ErrorHandlerTest extends \PHPUnit\Framework\TestCase
{
    public function testErrorHandlerNotice()
    {
        $errorHandler = new ErrorHandler();
        $errorHandler->register();

        Config::write('debug', true);

        ob_start();
        $a = $unkown + 1;
        $result = ob_get_clean();
               
        $this->assertStringContainsString('<div class="origin-error">', $result);
        $this->assertStringContainsString('<strong>NOTICE:</strong>', $result);
        $this->assertStringContainsString('Undefined variable: unkown', $result);
        $this->assertStringContainsString('line: <strong>64</strong>', $result);
    }

    public function testErrorHandlerWarning()
    {
        $errorHandler = new ErrorHandler();
        $errorHandler->register();

        Config::write('debug', true);

        ob_start();
        trigger_error('Passing invalid 2nd argument', E_WARNING);
        $result = ob_get_clean();
       
        $this->assertStringContainsString('<div class="origin-error">', $result);
        $this->assertStringContainsString('<strong>WARNING:</strong>', $result);
        $this->assertStringContainsString('Invalid error type specified', $result);
        $this->assertStringContainsString('line: <strong>81</strong>', $result);
    }

    public function testErrorHandlerError()
    {
        $errorHandler = new ErrorHandler();
        $errorHandler->register();

        Config::write('debug', true);

        ob_start();
        trigger_error('An error has occured', E_USER_ERROR);
        $result = ob_get_clean();
       
        $this->assertStringContainsString('<div class="origin-error">', $result);
        $this->assertStringContainsString('<strong>ERROR:</strong>', $result);
        $this->assertStringContainsString('An error has occured', $result);
        $this->assertStringContainsString('line: <strong>98</strong>', $result);
    }
    
    public function testErrorHandlerDeprecated()
    {
        $errorHandler = new ErrorHandler();
        $errorHandler->register();

        Config::write('debug', true);

        ob_start();
        trigger_error('Function has been deprecated', E_USER_DEPRECATED);
        $result = ob_get_clean();
   
        $this->assertStringContainsString('<div class="origin-error">', $result);
        $this->assertStringContainsString('<strong>DEPRECATED:</strong>', $result);
        $this->assertStringContainsString('Function has been deprecated', $result);
        $this->assertStringContainsString('line: <strong>115</strong>', $result);
    }

    public function testErrorHandlerSupressed()
    {
        $errorHandler = new ErrorHandler();
        $errorHandler->register();

        Config::write('debug', true);

        ob_start();
        @include 'somefile.php';
        $result = ob_get_clean();
       
        $this->assertEmpty($result);
    }

    public function testErrorHandlerDebugDisabled()
    {
        $errorHandler = new ErrorHandler();
        $errorHandler->register();

        Config::write('debug', false);

        ob_start();
        $a = $unkown + 1;
        $result = ob_get_clean();
        
        $this->assertEmpty($result);
    }

    public function testExceptionHandler()
    {
        $errorHandler = new MockErrorHandler();
        $errorHandler->register();

        Config::write('debug', true);
       
        $errorHandler->exceptionHandler(
            new NotFoundException('passwords.txt could not be found')
        );
    
        $this->assertStringContainsString('NotFoundException', $errorHandler->response());
        $this->assertStringContainsString('404', $errorHandler->response());
        $this->assertStringContainsString('passwords.txt could not be found', $errorHandler->response());
    }

    public function testExceptionHandlerDebugDisabled()
    {
        $errorHandler = new MockErrorHandler();
        $errorHandler->register();

        Config::write('debug', false);
       
        $errorHandler->exceptionHandler(
            new \Origin\Model\Exception\NotFoundException('passwords.txt could not be found')
        );
    
        $this->assertStringNotContainsString('NotFoundException', $errorHandler->response());
        $this->assertStringNotContainsString('404', $errorHandler->response());
        $this->assertStringNotContainsString('passwords.txt could not be found', $errorHandler->response());
        $this->assertStringContainsString('<h1>Page not found</h1>', $errorHandler->response());
    }

    public function testExceptionHandlerAjax()
    {
        $request = new Request('/api/users/fetch');
        $request->type('json');
        Router::request($request);

        $errorHandler = new MockErrorHandler();
        $errorHandler->register();

        Config::write('debug', true);
       
        $errorHandler->exceptionHandler(
            new NotFoundException('fetch not found')
        );
        // shows exact message
        $this->assertStringContainsString('{"error":{"message":"fetch not found","code":404}}', $errorHandler->response());
    }

    public function testExceptionHandlerAjaxDebugDisabled()
    {
        $request = new Request('/api/users/fetch');
        $request->type('json');
        Router::request($request);

        $errorHandler = new MockErrorHandler();
        $errorHandler->register();

        Config::write('debug', false);
       
        $errorHandler->exceptionHandler(
            new NotFoundException('Index not found')
        );

        $this->assertStringContainsString('{"error":{"message":"Index not found","code":404}}', $errorHandler->response());
    }

    public function testExceptionHandlerAjaxDebugDisabledInternalError()
    {
        $request = new Request('/api/users/fetch');
        $request->type('json');
        Router::request($request);

        $errorHandler = new MockErrorHandler();
        $errorHandler->register();

        Config::write('debug', false);
       
        $errorHandler->exceptionHandler(
            new InternalErrorException('Not Developed Yet')
        );

        $this->assertStringContainsString('{"error":{"message":"An Internal Error has Occured","code":500}}', $errorHandler->response());
    }

    public function testFatalError()
    {
        $errorHandler = new MockErrorHandler();
        $errorHandler->register();

        Config::write('debug', true);
       
        $errorHandler->fatalErrorHandler(E_ERROR, 'A Fatal Error has occured', 'dummy.php', 212);
        $this->assertStringContainsString('FatalErrorException', $errorHandler->response());
        $this->assertStringContainsString('500', $errorHandler->response());
        $this->assertStringContainsString('A Fatal Error has occured', $errorHandler->response());
    }

    public function testFatalErrorDisabled()
    {
        $errorHandler = new MockErrorHandler();
        $errorHandler->register();

        Config::write('debug', false);
       
        $errorHandler->fatalErrorHandler(E_ERROR, 'A Fatal Error has occured', 'dummy.php', 212);
       
        $this->assertStringNotContainsString('FatalErrorException', $errorHandler->response());
        $this->assertStringNotContainsString('500', $errorHandler->response());
        $this->assertStringNotContainsString('A Fatal Error has occured', $errorHandler->response());
        $this->assertStringContainsString('<h1>An Internal Error Has Occured</h1>', $errorHandler->response());
    }

    protected function setUp() : void
    {
        Router::request(new Request());
        Config::write('debug', true);
    }
    protected function tearDown() : void
    {
        Config::write('debug', true);

        $logger = Log::engine('default');
        $file = LOGS . DS . $logger->config('filename');
        if (file_exists($file)) {
            unlink($file);
        }
      
        restore_error_handler();
        restore_exception_handler();
    }
}
// class AbcHelper extends NumberHelper{} // fatal error Class 'App\Http\View\Helper\NumberHelper' not found
