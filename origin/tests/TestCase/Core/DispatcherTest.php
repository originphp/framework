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

namespace Origin\Test\Core;

use Origin\Core\Dispatcher;
use Origin\Controller\Controller;
use Origin\TestSuite\TestTrait;

use Origin\Core\Exception\RouterException;
use Origin\Controller\Exception\MissingControllerException;
use Origin\Controller\Exception\MissingMethodException;
use Origin\Controller\Exception\PrivateMethodException;

use Origin\Controller\Request;
use Origin\Controller\Response;

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
     * @return void
     */
    public function render($options=[])
    {
        return true;
    }
}

class MockDispatcher extends Dispatcher
{
    protected function getClass(string $controller, string $plugin = null)
    {
        return 'Origin\Test\Core\\' . $controller . 'Controller';
    }
}

class MockDispatcher2 extends Dispatcher
{
    use TestTrait;
}

class DispatcherTest extends \PHPUnit\Framework\TestCase
{
    public function testDispatch()
    {
        $Dispatcher = new MockDispatcher();
        $Dispatcher->start('blog_posts/index');
        $this->assertInstanceOf(Controller::class, $Dispatcher->controller());
    }
    public function testGetClass()
    {
        $Dispatcher = new MockDispatcher2();
        $this->assertEquals('App\Controller\WidgetsController', $Dispatcher->callMethod('getClass', ['Widgets',null]));
        $this->assertEquals('MyPlugin\Controller\WidgetsController', $Dispatcher->callMethod('getClass', ['Widgets','MyPlugin']));
    }
    public function testMissingController()
    {
        $this->expectException(MissingControllerException::class);

        $Dispatcher = new Dispatcher();
        $Dispatcher->start('apples/add');
    }
    public function testMissingControllerMethod()
    {
        $this->expectException(MissingMethodException::class);

        $Dispatcher = new MockDispatcher();
        $Dispatcher->start('blog_posts/does_not_exist');
    }
    public function testPrivateControllerMethod()
    {
        $this->expectException(PrivateMethodException::class);

        $Dispatcher = new MockDispatcher();
        $Dispatcher->start('blog_posts/reveal_password');
    }

    /**
     * Test if not route is found without messing with router
     */
    public function testNoRoute()
    {
        $this->expectException(RouterException::class);
        $Dispatcher = new MockDispatcher();
        $request = new Request();
        $request->params = []; // Skip Pages controller setup by default
        $Dispatcher->dispatch($request, new Response());
    }
}
