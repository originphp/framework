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
use Origin\TestSuite\OriginTestCase;
use Origin\TestSuite\TestTrait;
use Origin\Controller\Exception\MissingControllerException;

use Origin\Controller\Controller;

class WidgetsController extends Controller
{
    public $autoRender = false;
    
    public function index()
    {
    }
}

class WidgetsControllerTest extends OriginTestCase
{
    use IntegrationTestTrait;
    use TestTrait;
    public function dispatchRequest()
    {
        return new WidgetsController();
    }
}

class IntegrationTestTraitTest extends \PHPUnit\Framework\TestCase
{
    public function testGet()
    {
        $test = new WidgetsControllerTest();
        $test->get('/widgets/index');
        $this->assertEquals('GET', $_SERVER['REQUEST_METHOD']);
    }
    public function testPost()
    {
        $test = new WidgetsControllerTest();
        $data = ['id'=>512];
        $test->post('/widgets/index', $data);
        $this->assertEquals('POST', $_SERVER['REQUEST_METHOD']);
        $this->assertEquals($data, $_POST);
    }

    public function testDelete()
    {
        $test = new WidgetsControllerTest();
        $test->delete('/widgets/delete');
        $this->assertEquals('DELETE', $_SERVER['REQUEST_METHOD']);
    }

    public function testPut()
    {
        $test = new WidgetsControllerTest();
        $test->put('/widgets/edit');
        $this->assertEquals('PUT', $_SERVER['REQUEST_METHOD']);
    }

    public function testPatch()
    {
        $test = new WidgetsControllerTest();
        $test->patch('/widgets/edit');
        $this->assertEquals('PATCH', $_SERVER['REQUEST_METHOD']);
    }


    public function testEnv()
    {
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36';
        $test = new WidgetsControllerTest();
        // Test setting
        $test->env('HTTP_USER_AGENT', $userAgent);
        $this->assertEquals(['HTTP_USER_AGENT'=>$userAgent], $test->getProperty('env'));
    }

    public function testHeader()
    {
        $test = new WidgetsControllerTest();
        $header = "HTTP/1.0 404 Not Found";
        $test->header($header);
        $this->assertEquals([$header=>null], $test->getProperty('headers'));

        $test = new WidgetsControllerTest();
        $test->header('Location', 'http://www.example.com/');
        $this->assertEquals(['Location'=>'http://www.example.com/'], $test->getProperty('headers'));
    }

    public function testSession()
    {
        $test = new WidgetsControllerTest();
        $data1 = ['Widget.name'=>'foo'];
        $test->session($data1);
        $data2 = ['Widget.serial'=>'12345'];
        $test->session($data2);
        $expected = [
            'Widget.name'=>'foo',
            'Widget.serial'=>'12345'
        ];
        $this->assertEquals($expected, $test->getProperty('session'));
    }

    /**
     * @depends testEnv
     * @depends testSession
     * @depends testHeader
     *
     * @return void
     */
    public function testSendRequest()
    {
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36';
        $header = 'WWW-Authenticate: Negotiate';
        $sessionData = [ 'Widget.serial'=>'12345'];

        $test = new WidgetsControllerTest();
        
        $test->env('HTTP_USER_AGENT', $userAgent);
        $test->header($header);
        $test->session($sessionData);

        $test->get('/widgets/index');
        $this->assertEquals($userAgent, $_SERVER['HTTP_USER_AGENT']);
        $expected = ['serial'=>'12345'];
        $this->assertEquals($expected, $_SESSION['Widget']);
    }
}
