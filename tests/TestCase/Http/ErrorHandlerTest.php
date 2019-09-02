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
use Origin\Exception\NotFoundException;
use Origin\Exception\InternalErrorException;

class MockErrorHandler extends ErrorHandler
{
    public $response = null;
    public $statusCode = null;

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
               
        $this->assertContains('<div class="origin-error">', $result);
        $this->assertContains('<strong>NOTICE:</strong>', $result);
        $this->assertContains('Undefined variable: unkown', $result);
        $this->assertContains('line: <strong>56</strong>', $result);
    }

    public function testErrorHandlerWarning()
    {
        $errorHandler = new ErrorHandler();
        $errorHandler->register();

        Config::write('debug', true);

        ob_start();
        trigger_error('Passing invalid 2nd argument', E_WARNING);
        $result = ob_get_clean();
       
        $this->assertContains('<div class="origin-error">', $result);
        $this->assertContains('<strong>WARNING:</strong>', $result);
        $this->assertContains('Invalid error type specified', $result);
        $this->assertContains('line: <strong>73</strong>', $result);
    }

    public function testErrorHandlerError()
    {
        $errorHandler = new ErrorHandler();
        $errorHandler->register();

        Config::write('debug', true);

        ob_start();
        trigger_error('An error has occured', E_USER_ERROR);
        $result = ob_get_clean();
       
        $this->assertContains('<div class="origin-error">', $result);
        $this->assertContains('<strong>ERROR:</strong>', $result);
        $this->assertContains('An error has occured', $result);
        $this->assertContains('line: <strong>90</strong>', $result);
    }
    
    public function testErrorHandlerDeprecated()
    {
        $errorHandler = new ErrorHandler();
        $errorHandler->register();

        Config::write('debug', true);

        ob_start();
        trigger_error('Function has been deprecated', E_USER_DEPRECATED);
        $result = ob_get_clean();
   
        $this->assertContains('<div class="origin-error">', $result);
        $this->assertContains('<strong>DEPRECATED:</strong>', $result);
        $this->assertContains('Function has been deprecated', $result);
        $this->assertContains('line: <strong>107</strong>', $result);
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
    
        $this->assertContains('NotFoundException', $errorHandler->response);
        $this->assertContains('404', $errorHandler->response);
        $this->assertContains('passwords.txt could not be found', $errorHandler->response);
    }

    public function testExceptionHandlerDebugDisabled()
    {
        $errorHandler = new MockErrorHandler();
        $errorHandler->register();

        Config::write('debug', false);
       
        $errorHandler->exceptionHandler(
            new \Origin\Model\Exception\NotFoundException('passwords.txt could not be found')
        );
    
        $this->assertNotContains('NotFoundException', $errorHandler->response);
        $this->assertNotContains('404', $errorHandler->response);
        $this->assertNotContains('passwords.txt could not be found', $errorHandler->response);
        $this->assertContains('<h1>Page not found</h1>', $errorHandler->response);
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
        $this->assertContains('{"error":{"message":"fetch not found","code":404}}', $errorHandler->response);
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

        $this->assertContains('{"error":{"message":"Index not found","code":404}}', $errorHandler->response);
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

        $this->assertContains('{"error":{"message":"An Internal Error has Occured","code":500}}', $errorHandler->response);
    }

    public function testFatalError()
    {
        $errorHandler = new MockErrorHandler();
        $errorHandler->register();

        Config::write('debug', true);
       
        $errorHandler->fatalErrorHandler(E_ERROR, 'A Fatal Error has occured', 'dummy.php', 212);
        $this->assertContains('FatalErrorException', $errorHandler->response);
        $this->assertContains('500', $errorHandler->response);
        $this->assertContains('A Fatal Error has occured', $errorHandler->response);
    }

    public function testFatalErrorDisabled()
    {
        $errorHandler = new MockErrorHandler();
        $errorHandler->register();

        Config::write('debug', false);
       
        $errorHandler->fatalErrorHandler(E_ERROR, 'A Fatal Error has occured', 'dummy.php', 212);
       
        $this->assertNotContains('FatalErrorException', $errorHandler->response);
        $this->assertNotContains('500', $errorHandler->response);
        $this->assertNotContains('A Fatal Error has occured', $errorHandler->response);
        $this->assertContains('<h1>An Internal Error Has Occured</h1>', $errorHandler->response);
    }

    protected function setUp() : void
    {
        parent::setUp();
        Router::request(new Request());
        Config::write('debug', true);
    }
    protected function tearDown() : void
    {
        parent::tearDown();
        
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
// class AbcHelper extends NumberHelper{} // fatal error Class 'App\View\Helper\NumberHelper' not found
