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

use Origin\TestSuite\IntegrationTestTrait;
use Origin\Controller\Exception\MissingControllerException;
use Origin\Core\Configure;
use Origin\Core\Autoloader;
use Origin\Controller\Controller;
use Origin\Http\Request;
use Origin\Http\Response;
use Origin\TestSuite\TestTrait;
use PHPUnit\Framework\AssertionFailedError;

class IntegrationTestTraitTest extends \PHPUnit\Framework\TestCase
{
    use IntegrationTestTrait;
    use TestTrait;
    
    public function testGet()
    {
        $this->get('/posts/index');
        $this->assertEquals('GET', $_SERVER['REQUEST_METHOD']);
    }
    public function testPost()
    {
        $data = ['id'=>512];
        $this->post('/posts/index', $data);
        $this->assertEquals('POST', $_SERVER['REQUEST_METHOD']);
        $this->assertEquals($data, $_POST);
    }

    public function testDelete()
    {
        $this->delete('/posts/index');
        $this->assertEquals('DELETE', $_SERVER['REQUEST_METHOD']);
    }

    public function testPut()
    {
        $this->put('/posts/index');
        $this->assertEquals('PUT', $_SERVER['REQUEST_METHOD']);
    }

    public function testPatch()
    {
        $this->patch('/posts/index');
        $this->assertEquals('PATCH', $_SERVER['REQUEST_METHOD']);
    }

    public function testController()
    {
        $this->get('/posts/index');
        $this->assertInstanceOf(Controller::class, $this->controller());
    }

    public function testControllerFail()
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('No controller');
        $this->controller();
    }

    public function testRequest()
    {
        $this->get('/posts/index');
        $this->assertInstanceOf(Request::class, $this->request());
    }

    public function testRequestFail()
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('No request');
        $this->request();
    }

    public function testResponse()
    {
        $this->get('/posts/index');
        $this->assertInstanceOf(Response::class, $this->response());
    }

    public function testResponseFail()
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('No response');
        $this->response();
    }

    
    public function testViewVariable()
    {
        $this->get('/posts/index');
        $controller = $this->controller();
        $controller->set('foo', 'bar');

        $this->assertEquals('bar', $this->viewVariable('foo'));
        $this->assertNull($this->viewVariable('nonExistant'));
    }

    public function testViewVariableFail()
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('No request');
        $this->viewVariable('beforeGet');
    }


    public function testEnv()
    {
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36';

        // Test setting
        $this->env('HTTP_USER_AGENT', $userAgent);
        $this->assertEquals(['HTTP_USER_AGENT'=>$userAgent], $this->getProperty('env'));
        
        $this->get('/posts/index');
    }

    public function testCookies()
    {

        // Test setting
        $this->cookie('foo', 'bar');
        $this->assertEquals(['foo'=>'bar'], $this->getProperty('cookies'));

        $this->get('/posts/index');
        $this->assertEquals('bar', $this->response->cookies('foo'));
    }


    public function testHeader()
    {
        $header = "HTTP/1.0 404 Not Found";
        $this->header($header);
        $this->assertEquals([$header=>null], $this->getProperty('headers'));

        $this->header('Location', 'http://www.example.com/');
        $this->assertEquals(['Location'=>'http://www.example.com/','HTTP/1.0 404 Not Found'=>null], $this->getProperty('headers'));
   
        $this->get('/posts/index');
    }

    public function testSession()
    {
        $data1 = ['Widget.name'=>'foo'];
        $this->session($data1);
        $data2 = ['Widget.serial'=>'12345'];
        $this->session($data2);
        $expected = [
            'Widget.name'=>'foo',
            'Widget.serial'=>'12345'
        ];
        $this->assertEquals($expected, $this->getProperty('session'));

        $this->get('/posts/index');
    }

    public function testResponseOk()
    {
        $this->response = new Response();
        
        $this->response->statusCode(200);
        $this->assertResponseOk();

        $this->response->statusCode(204);
        $this->assertResponseOk();
    }

    public function testResponseSuccess()
    {
        $this->response = new Response();

        $this->response->statusCode(200);
        $this->assertResponseSuccess();

        $this->response->statusCode(308);
        $this->assertResponseSuccess();
    }

    public function testResponseError()
    {
        $this->response = new Response();
       
        $this->response->statusCode(400);
        $this->assertResponseError();

        $this->response->statusCode(429);
        $this->assertResponseError();
    }

    public function testResponseFailure()
    {
        $this->response = new Response();
    
        $this->response->statusCode(500);
        $this->assertResponseFailure();

        $this->response->statusCode(505);
        $this->assertResponseFailure();
    }

    public function testResponseCode()
    {
        $this->response = new Response();
        $this->response->statusCode(404);
        $this->assertResponseCode(404);

        $this->response = null;
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('No response');
        $this->assertResponseCode(200);
    }
    public function testResponseContains()
    {
        $this->get('/posts/index');
        $this->assertResponseContains('Posts Home Page');
    }
    public function testResponseNotContains()
    {
        $this->get('/posts/index');
        $this->assertResponseNotContains('Contacts Home Page');
    }
    public function testResponseEquals()
    {
        $this->get('/posts/list');
        $this->assertResponseEquals('{"error":"Noting to list"}');
    }
    public function testResponseNotEquals()
    {
        $this->get('/posts/list');
        $this->assertResponseNotEquals('{"data":{id:1234}}');
    }
    public function testRedirect()
    {
        $this->header('Location', '/posts/agree');
        $this->get('/posts/list');
        $this->assertRedirect();
        $this->assertRedirect('/posts/agree');

        $this->response = null;
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('No response');
        $this->assertRedirect();
    }
    public function testNoRedirect()
    {
        $this->get('/posts/list');
        $this->assertNoRedirect();
    }

    public function testRedirectContains()
    {
        $this->header('Location', '/posts/edit?user=1234');
        $this->get('/posts/list');
        $this->assertRedirectContains('1234');
    }
    public function testRedirectContainsFail()
    {
        $this->get('/posts/list');
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('No location');
        $this->assertRedirectContains('1234');
    }
    public function testRedirectNotContains()
    {
        $this->header('Location', '/posts/edit?user=1234');
        $this->get('/posts/list');
        $this->assertRedirectNotContains('5678');
    }
    public function testRedirectNotContainsFail()
    {
        $this->get('/posts/list');
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('No location');
        $this->assertRedirectNotContains('1234');
    }
    public function testResponseNotEmpty()
    {
        $this->get('/posts/index');
        $this->assertResponseNotEmpty();
    }
    public function testResponseEmpty()
    {
        $this->response = new Response();
        $this->assertResponseEmpty();
    }
    public function testHeaderContains()
    {
        $this->header('Location', '/posts/edit/1024');
        $this->get('/posts/list');
        $this->assertHeaderContains('Location', '1024');
    }
    public function testHeaderNotContains()
    {
        $this->header('Location', '/posts/edit/1024');
        $this->get('/posts/list');
        $this->assertHeaderNotContains('Location', '512');
    }
}
