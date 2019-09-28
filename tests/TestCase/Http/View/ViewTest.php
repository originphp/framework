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

namespace Origin\Test\Http\View;

use Origin\Http\View\View;
use Origin\Http\Request;
use Origin\Http\Response;
use Origin\Http\View\Helper\Helper;
use Origin\Exception\Exception;
use Origin\TestSuite\TestTrait;
use Origin\Http\Controller\Controller;
use Origin\Http\View\Exception\MissingViewException;
use Origin\Http\View\Exception\MissingLayoutException;
use Origin\Http\View\Exception\MissingElementException;

class TestsController extends Controller
{
    /*public function initalize(){
      $this->loadHelper('View',['className'=>'Origin\Test\Http\View\ViewHelper']);
    }*/
}

class TesterHelper extends Helper
{
}

class MockView extends View
{
    use TestTrait; // add invokeMethod

    public $mockFiles = [];

    /**
     * Used to overide files for testing rendering views etc.
     */
    public $overideFiles = [];

    public function fileExists(string $filename)
    {
        return in_array($filename, $this->mockFiles) or file_exists($filename);
    }

    public function setFile(string $filename)
    {
        $this->mockFiles = [$filename];
    }

    protected function getElementFilename(string $name)
    {
        if (isset($this->overideFiles[$name])) {
            return $this->overideFiles[$name];
        }

        return parent::getElementFilename($name);
    }

    protected function getLayoutFilename(string $name)
    {
        if (isset($this->overideFiles[$name])) {
            return $this->overideFiles[$name];
        }

        return parent::getLayoutFilename($name);
    }

    protected function getViewFilename(string $name)
    {
        if (isset($this->overideFiles[$name])) {
            return $this->overideFiles[$name];
        }

        return parent::getViewFilename($name);
    }
}

class ViewTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->View = new View(new TestsController(new Request(), new Response()));
    }

    public function testConstruct()
    {
        $request = new Request('tests/edit/2048');
        $response = new Response();
        $controller = new TestsController($request, $response);
        $controller->set('framework', 'Origin');
        $view = new View($controller);
        $this->assertEquals('Tests', $view->name);
        $this->assertEquals(['framework' => 'Origin'], $view->vars);
    }

    public function testLoadHelper()
    {
        $request = new Request('tests/edit/2048');
        $controller = new TestsController($request, new Response());
        $view = new View($controller);

        $view->loadHelper('Tester', ['className' => 'Origin\Test\Http\View\TesterHelper']);
        $this->assertObjectHasAttribute('Tester', $view);
        $this->assertInstanceOf('Origin\Test\Http\View\TesterHelper', $view->Tester);
    }

    public function testViewFilename()
    {
        $request = new Request('tests/edit/2048');
        $controller = new TestsController($request, new Response());
        $view = new MockView($controller);

        $expected = SRC.'/Http/View/Tests/index.ctp';
        $view->setFile($expected); // Prevent exception
        $result = $view->callMethod('getViewFilename', ['index']);

        $expected = SRC.'/Http/View/Rest/json.ctp';
        $view->setFile($expected); // Prevent exception
        $result = $view->callMethod('getViewFilename', ['/Rest/json']);

        $expected = PLUGINS. '/make/src/Http/View/MyController/action.ctp';
        $view->setFile($expected); // Prevent exception

        $result = $view->callMethod('getViewFilename', ['Make.MyController/action']);

        $this->assertEquals($expected, $result);
    }

    public function testLayoutFilename()
    {
        $request = new Request('tests/edit/2048');
        $controller = new TestsController($request, new Response());
        $view = new MockView($controller);

        $expected = SRC.'/Http/View/Layout/bootstrap.ctp';
        $view->setFile($expected); // Prevent exception
        $result = $view->callMethod('getLayoutFilename', ['bootstrap']);

        $expected = PLUGINS.'/contact_manager/src/Http/View/Layout/bootstrap.ctp';
        $view->setFile($expected); // Prevent exception
        $result = $view->callMethod('getLayoutFilename', ['ContactManager.bootstrap']);

        $this->assertEquals($expected, $result);
    }

    public function testElementFilename()
    {
        $request = new Request('tests/edit/2048');
        $controller = new TestsController($request, new Response());
        $view = new MockView($controller);

        $expected = SRC.'/Http/View/Element/recordTable.ctp';
        $view->setFile($expected); // Prevent exception
        $result = $view->callMethod('getElementFilename', ['recordTable']);

        $expected = PLUGINS.'/contact_manager/src/Http/View/Element/recordTable.ctp';
        $view->setFile($expected); // Prevent exception
        $result = $view->callMethod('getElementFilename', ['ContactManager.recordTable']);

        $this->assertEquals($expected, $result);
    }

    public function testSettersAndGetters()
    {
        $request = new Request('tests/edit/2048');
        $controller = new TestsController($request, new Response());
        $view = new MockView($controller);
        $view->set('framework', 'Origin');
        $this->assertEquals('Origin', $view->get('framework'));
        $this->assertEquals(['framework' => 'Origin'], $view->fetch('vars'));
    }

    public function testRender()
    {
        $request = new Request('tests/edit/2048');
        $controller = new TestsController($request, new Response());
        $view = new MockView($controller);

        $view->overideFiles = [
            'layout' => ORIGIN.'/tests/TestCase/Http/View/layout.ctp',
            'edit' => ORIGIN.'/tests/TestCase/Http/View/action.ctp',
            'element' => ORIGIN.'/tests/TestCase/Http/View/element.ctp',
        ];

        $view->set('title', 'Layout Loaded');
        $result = $view->callMethod('render', ['edit', 'layout']);
        $expected = '<h1>Layout Loaded<h1><h2>Action Loaded: edit</h2><span>Element Loaded</span>';
        $this->assertEquals($expected, str_replace("\n", '', $result));
    }

    /**
     * New Tests - based upon new features such as testApp etc, these previously not avaiable.
     *
     */

    public function testViewRender()
    {
        $this->View->loadHelper('Flash');
        $result = $this->View->render('/Posts/index', 'default');
        $this->assertStringContainsString('<h1>Posts Home Page</h1>', $result); // view
        $this->assertStringContainsString('<title>Tests</title>', $result); // Layout
    }

    public function testViewRenderPlugin()
    {
        $this->View->loadHelper('Flash');
        $result = $this->View->render('Widget.Widgets/items', false);
        $this->assertEquals('<h2>Widget Items</h2>', $result);

        $request = new Request('tests/edit/2048');
        $controller = new Controller($request, new Response());
        $controller->name = 'Widgets';
        $controller->request->params('controller', 'Widgets');
        $controller->request->params('plugin', 'Widget');

        $view = new View($controller);
        $result = $view->render('items');
        $this->assertEquals('<h2>Widget Items</h2>', $result);
    }

    public function testTitle()
    {
        $this->assertNull($this->View->title());
        $this->View->set('title', 'foo');
        $this->assertEquals('foo', $this->View->title());
    }

    public function testFetch()
    {
        $this->assertIsArray($this->View->fetch('vars'));
        $this->assertNull($this->View->fetch('foo'));
    }

    public function testMissingElementException()
    {
        $this->expectException(MissingElementException::class);
        $this->View->element('i-dont-exist');
    }
    public function testMissingViewException()
    {
        $this->expectException(MissingViewException::class);
        $this->View->render('i-dont-exist');
    }
    public function testMissingLayoutException()
    {
        $this->expectException(MissingLayoutException::class);
        $this->View->render('/Posts/index', 'non-existant-layout');
    }

    public function testGetNonExistantHelper()
    {
        $this->expectException(Exception::class);
        $this->View->IDontExist->foo();
    }
}
