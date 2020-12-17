<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
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

use Origin\Http\Request;
use Origin\Http\Response;
use Origin\Http\Dispatcher;

use Origin\TestSuite\TestTrait;
use Origin\Http\Controller\Controller;
use Origin\Core\Exception\RouterException;
use Origin\Http\Controller\Exception\MissingMethodException;

use Origin\Http\Controller\Exception\PrivateMethodException;
use Origin\Http\Controller\Exception\MissingControllerException;

class BlogPostsController extends Controller
{
    protected function reveal_password()
    {
    }
   
    public function index()
    {
    }
    /**
     * We want this to run
     *
     * @param string $view
     * @return Response
     */
    public function render($options = []): Response
    {
        return $this->response;
    }
}

class AnotherMockRequest extends Request
{
    public function reset()
    {
        $this->params = [];
    }
}

class MockDispatcher extends Dispatcher
{
    protected function getClass(string $controller, string $plugin = null, string $prefix = null): string
    {
        return 'Origin\Test\Http\\' . $controller . 'Controller';
    }
}

class MockDispatcher2 extends Dispatcher
{
    use TestTrait;
}

    /*
$response = $this->dispatch(new Request($url), new Response());
        $response->send();
    */
class DispatcherTest extends \PHPUnit\Framework\TestCase
{
    public function testDispatch()
    {
        $Dispatcher = new MockDispatcher();
        $Dispatcher->dispatch(new Request('blog_posts/index'), new Response());
        $this->assertInstanceOf(Controller::class, $Dispatcher->controller());
    }
    public function testGetClass()
    {
        $Dispatcher = new MockDispatcher2();
        $this->assertEquals('App\Http\Controller\WidgetsController', $Dispatcher->callMethod('getClass', ['Widgets',null]));
        $this->assertEquals('MyPlugin\Http\Controller\WidgetsController', $Dispatcher->callMethod('getClass', ['Widgets','MyPlugin']));
    }
    public function testMissingController()
    {
        $this->expectException(MissingControllerException::class);

        $Dispatcher = new Dispatcher();
        $Dispatcher->dispatch(new Request('apples/add'), new Response());
    }
    public function testMissingControllerMethod()
    {
        $this->expectException(MissingMethodException::class);

        $Dispatcher = new MockDispatcher();
        $Dispatcher->dispatch(new Request('blog_posts/does_not_exist'), new Response());
    }
    public function testPrivateControllerMethod()
    {
        $this->expectException(PrivateMethodException::class);

        $Dispatcher = new MockDispatcher();
        $Dispatcher->dispatch(new Request('blog_posts/reveal_password'), new Response());
    }

    /**
     * Test if not route is found without messing with router
     */
    public function testNoRoute()
    {
        $this->expectException(RouterException::class);
        $Dispatcher = new MockDispatcher();
        $request = new AnotherMockRequest();
        $request->reset(); // Skip Pages controller setup by default
        $Dispatcher->dispatch($request, new Response());
    }
}
