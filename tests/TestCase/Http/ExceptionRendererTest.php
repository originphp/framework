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

use Origin\Http\Request;
use Origin\Http\Response;
use Origin\Http\ExceptionRenderer;
use Origin\Exception\NotFoundException;
use Origin\Exception\InternalErrorException;

class ExceptionRendererTest extends \PHPUnit\Framework\TestCase
{
    public function testRenderNotFound()
    {
        $exceptionRenderer = new ExceptionRenderer(new Request(), new Response());
        $exception = new NotFoundException('Some Exception');
        $response = $exceptionRenderer->render($exception);

        $this->assertContains('Some Exception', $response->body());
        $this->assertEquals(404, $response->statusCode());
    }
    public function testRenderInternalError()
    {
        $exceptionRenderer = new ExceptionRenderer(new Request(), new Response());
        $exception = new InternalErrorException('Its gone pete tong');
        $response = $exceptionRenderer->render($exception);

        $this->assertContains('An Internal Error Has Occured', $response->body());
        $this->assertEquals(500, $response->statusCode());
    }

    public function testRenderNotFoundNonHttp()
    {
        $exceptionRenderer = new ExceptionRenderer(new Request(), new Response());
        $exception = new \Origin\Model\Exception\NotFoundException('id = 12345');
        $response = $exceptionRenderer->render($exception);
  
        $this->assertNotContains('12345', $response->body());
        $this->assertContains('Page not found', $response->body());
        $this->assertEquals(404, $response->statusCode());
    }

    public function testRenderNotFoundAjax()
    {
        $exceptionRenderer = new ExceptionRenderer(new Request('/somefile.json'), new Response());
        $exception = new NotFoundException('Some Exception');
        $response = $exceptionRenderer->render($exception);
        $this->assertContains('{"error":{"message":"Some Exception","code":404}}', $response->body());
        $this->assertEquals(404, $response->statusCode());
    }

    public function testRenderNotFoundNonHttpAjax()
    {
        $exceptionRenderer = new ExceptionRenderer(new Request('/somefile.json'), new Response());
        $exception = new \Origin\Model\Exception\NotFoundException('id = 12345');
        $response = $exceptionRenderer->render($exception);
        $this->assertContains('{"error":{"message":"Not Found","code":404}}', $response->body());
        $this->assertEquals(404, $response->statusCode());
    }
    
    public function testRenderInternalErrorAjax()
    {
        $exceptionRenderer = new ExceptionRenderer(new Request('/somefile.json'), new Response());
        $exception = new InternalErrorException('Its gone pete tong');
        $response = $exceptionRenderer->render($exception);
        $this->assertContains('{"error":{"message":"An Internal Error has Occured","code":500}}', $response->body());
        $this->assertEquals(500, $response->statusCode());
    }

    public function testRenderDebug()
    {
        $exceptionRenderer = new ExceptionRenderer(new Request('/somefile.json'), new Response());
        $exception = new InternalErrorException('Its gone pete tong');
        $response = $exceptionRenderer->render($exception, true);

        $this->assertContains('Its gone pete tong', $response->body());
        $this->assertEquals(500, $response->statusCode());
    }
}
