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

namespace Origin\Test\View;

use Origin\TestSuite\TestTrait;
use Origin\View\View;
use Origin\View\Helper\Helper;
use Origin\Controller\Controller;
use Origin\Controller\Request;
use Origin\Controller\Response;

class TestsController extends Controller
{
    /*public function initalize(){
      $this->loadHelper('View',['className'=>'Origin\Test\View\ViewHelper']);
    }*/
}

class TesterHelper extends Helper
{
}

class MockView extends View
{
    use TestTrait; // add invokeMethod

    public $mockFiles = array();

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
    public function setUp()
    {
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

        $view->loadHelper('Tester', ['className' => 'Origin\Test\View\TesterHelper']);
        $this->assertObjectHasAttribute('Tester', $view);
        $this->assertInstanceOf('Origin\Test\View\TesterHelper', $view->Tester);
    }

    public function testViewFilename()
    {
        $request = new Request('tests/edit/2048');
        $controller = new TestsController($request, new Response());
        $view = new MockView($controller);

        $expected = SRC.'/View/Tests/index.ctp';
        $view->setFile($expected); // Prevent exception
        $result = $view->callMethod('getViewFilename', ['index']);

        $expected = SRC.'/View/Rest/json.ctp';
        $view->setFile($expected); // Prevent exception
        $result = $view->callMethod('getViewFilename', ['/Rest/json']);

        $this->assertEquals($expected, $result);
    }

    public function testLayoutFilename()
    {
        $request = new Request('tests/edit/2048');
        $controller = new TestsController($request, new Response());
        $view = new MockView($controller);

        $expected = SRC.'/View/Layout/bootstrap.ctp';
        $view->setFile($expected); // Prevent exception
        $result = $view->callMethod('getLayoutFilename', ['bootstrap']);

        $expected = PLUGINS.'/ContactManager/src/View/Layout/bootstrap.ctp';
        $view->setFile($expected); // Prevent exception
        $result = $view->callMethod('getLayoutFilename', ['ContactManager.bootstrap']);

        $this->assertEquals($expected, $result);
    }

    public function testElementFilename()
    {
        $request = new Request('tests/edit/2048');
        $controller = new TestsController($request, new Response());
        $view = new MockView($controller);

        $expected = SRC.'/View/Element/recordTable.ctp';
        $view->setFile($expected); // Prevent exception
        $result = $view->callMethod('getElementFilename', ['recordTable']);

        $expected = PLUGINS.'/ContactManager/src/View/Element/recordTable.ctp';
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
      'layout' => ORIGIN.'/tests/TestCase/View/layout.ctp',
      'edit' => ORIGIN.'/tests/TestCase/View/action.ctp',
      'element' => ORIGIN.'/tests/TestCase/View/element.ctp',
    ];

        $view->set('title', 'Layout Loaded');
        $result = $view->callMethod('render', ['edit', 'layout']);
        $expected = '<h1>Layout Loaded<h1><h2>Action Loaded: edit</h2><span>Element Loaded</span>';
        $this->assertEquals($expected, str_replace("\n", '', $result));
    }
}
