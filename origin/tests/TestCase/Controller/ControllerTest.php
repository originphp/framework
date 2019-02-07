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

namespace Origin\Test\Controller;

use Origin\Controller\Controller;
use Origin\Model\ModelRegistry;
use Origin\Controller\Component\Component;
use Origin\View\Helper\Helper;
use Origin\Controller\Request;
use Origin\Controller\Response;
use Origin\Core\Router;
use Origin\Model\Model;

use Origin\Model\ConnectionManager;

class Pet extends Model
{
    public $datasource = 'test';
}
class TestModel
{
    public $name = 'TestModel';
}

class TesterComponent extends Component
{
}

class TesterHelper extends Helper
{
}

class MockPaginator
{
    public function paginate($object, $config)
    {
        return [$object,$config];
    }
}

class TestsController extends Controller
{
    public $initialized = false;

    public function action()
    {
    }

    public function initialize()
    {
        $this->initialized = true;
    }

    public function setMockRegistry($registry)
    {
        $this->componentRegistry = $registry;
    }
}

class ControllerTest extends \PHPUnit\Framework\TestCase
{
    public $controller = null;

    public function setUp()
    {
        Router::add('/:controller/:action/*');
        $request = new Request('articles/store/100');
        $response = new Response();
        $this->controller = new Controller($request, $response);
    }

    public function testControllerName()
    {
        $request = new Request('user_profiles/edit/256');
        $controller = new Controller($request, new Response());

        $this->assertEquals('UserProfiles', $controller->name);
        $this->assertEquals('UserProfile', $controller->modelName);

        $controller = new TestsController();
        $this->assertEquals('Tests', $controller->name);
    }

    public function testConstruct()
    {
        $request = new Request('tests/view/512');
        $response = new Response();
        $controller = new TestsController($request, $response);

        $this->assertEquals($request, $controller->request);
        $this->assertEquals($response, $controller->response);

        $this->assertNotEmpty($controller->componentRegistry());
        $this->assertInstanceOf('Origin\Controller\Component\ComponentRegistry', $controller->componentRegistry());

        $this->assertEquals('Test', $controller->modelName);

        $this->assertTrue($controller->initialized);
    }

    public function testLoadComponent()
    {
        $request = new Request('tests/view/512');
        $controller = new TestsController($request, new Response());

        $controller->loadComponent('Tester', ['className' => 'Origin\Test\Controller\TesterComponent']);
        $this->assertObjectHasAttribute('Tester', $controller);
        $this->assertInstanceOf('Origin\Test\Controller\TesterComponent', $controller->Tester);
    }

    public function testLoadComponents()
    {
        $request = new Request('tests/view/512');
        $controller = new TestsController($request, new Response());

        $controller = new TestsController($request, new Response());
        $controller->loadComponents(['Tester' => ['className' => 'Origin\Test\Controller\TesterComponent']]);
        $this->assertObjectHasAttribute('Tester', $controller);
    }

    public function testLoadHelper()
    {
        $request = new Request('tests/view/512');
        $controller = new TestsController($request, new Response());
        $controller->loadHelper('Tester', ['className' => 'Origin\Test\Controller\TesterHelper']);
        $this->assertArrayHasKey('Tester', $controller->viewHelpers);
    }

    public function testLoadHelpers()
    {
        $request = new Request('tests/view/512');
        $controller = new TestsController($request, new Response());
        $controller->loadHelpers(['Tester' => ['className' => 'Origin\Test\Controller\TesterHelper']]);
        $this->assertArrayHasKey('Tester', $controller->viewHelpers);
    }

    public function testSet()
    {
        $request = new Request('tests/edit/1024');
        $controller = new TestsController($request, new Response());

        $apples = array('granny smith', 'pink lady');
        $controller->set('apples', $apples);
        $this->assertArrayHasKey('apples', $controller->viewVars);
        $this->assertEquals($controller->viewVars['apples'], $apples);

        $fruits = array('apple', 'banana', 'orange');
        $controller->set('fruits', $fruits);
        $this->assertArrayHasKey('fruits', $controller->viewVars);
        $this->assertEquals($controller->viewVars['fruits'], $fruits);

        $combo = array(
      'apples' => array('granny smith', 'pink lady'),
      'bananas' => array('cavendish', 'Baby (Niño)'),
      'oranges' => array('blood', 'clementine'),
    );
        $controller->set('combo', $combo);
        $this->assertArrayHasKey('combo', $controller->viewVars);
        $this->assertEquals($controller->viewVars['combo'], $combo);
    }

    public function testIsAccessible()
    {
        $request = new Request('tests/edit/2048');
        $controller = new TestsController($request, new Response());
        $this->assertTrue($controller->isAccessible('action'));
        $this->assertFalse($controller->isAccessible('startup'));
    }

    public function testLoadModel()
    {
        $request = new Request('tests/edit/2048');
        $controller = new TestsController($request, new Response());
        $Test = new TestModel();
        ModelRegistry::set('Test', $Test);
        $lazyLoaded = $controller->Test;
        $this->assertEquals($Test, $lazyLoaded);

        $Test->name = 'Test2Model';
        ModelRegistry::set('Test2', $Test);

        $this->assertEquals($Test, $controller->loadModel('Test2'));
        $this->assertEquals($Test, $controller->Test2);
        $this->assertNull($controller->NonExistantModel);
    }

    public function testCallbacksStartup()
    {
        $request = new Request('tests/edit/2048');

        $controller = $this->getMockBuilder('Origin\Test\Controller\TestsController')
            ->setMethods(['startup'])
            ->setConstructorArgs([$request, new Response()])
            ->getMock();

        $controller->expects($this->once())
    ->method('startup');

        $components = $this->getMockBuilder('Origin\Controller\Component\ComponentRegistry')
            ->setMethods(['call'])
            ->setConstructorArgs([$controller])
            ->getMock();

        $components->expects($this->once())
     ->method('call')
       ->with('startup');

        $controller->setMockRegistry($components);

        $controller->startupProcess();
    }

    public function testCallbacksShutdown()
    {
        $request = new Request('tests/edit/2048');

        $controller = $this->getMockBuilder('Origin\Test\Controller\TestsController')
            ->setMethods(['shutdown'])
            ->setConstructorArgs([$request, new Response()])
            ->getMock();

        $controller->expects($this->once())
    ->method('shutdown');

        $components = $this->getMockBuilder('Origin\Controller\Component\ComponentRegistry')
            ->setMethods(['call'])
            ->setConstructorArgs([$controller])
            ->getMock();

        $components->expects($this->once())
     ->method('call')
       ->with('shutdown');

        $controller->setMockRegistry($components);

        $controller->shutdownProcess();
    }

    /**
     * Create tmp view file
     *
     * @return void
     */
    public function testRender()
    {
        $tmpFolder = VIEW . DS . 'Tests';
        $expected = '<h1>Test Render<h1>';
        mkdir($tmpFolder);
        file_put_contents($tmpFolder.DS . 'edit.ctp', $expected);

        $request = new Request('tests/edit/1024');
        $controller = new TestsController($request, new Response());
        $controller->layout = false;
        $controller->render();

        $this->assertEquals($expected, $controller->response->body());
        unlink($tmpFolder.DS . 'edit.ctp');
        rmdir($tmpFolder);
    }

    public function testRedirect()
    {
        $request = new Request('tests/edit/2048');
        $response = $this->getMockBuilder('Origin\Controller\Response')
           ->setMethods(['header', 'send','statusCode','stop'])
           ->getMock();

        $controller = new Controller($request, $response);

        $response->expects($this->once())
        ->method('statusCode')
          ->with(302);

        $response->expects($this->once())
           ->method('header')
             ->with('Location', '/tests/view/2048');

        $response->expects($this->once())
           ->method('send');

        
        $this->assertNull($controller->redirect(array('action' => 'view', 2048)));
    }

    public function testPaginate()
    {
        # Create Table
        $connection = ConnectionManager::get('test');
        $connection->execute('CREATE TABLE IF NOT EXISTS pets ( id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(20));');
        $connection->execute('TRUNCATE TABLE pets');

        # Create Dummy Data
        $Pet = new Pet();
        ModelRegistry::set('Pet', $Pet);
        for ($i=0;$i<100;$i++) {
            $Pet->save($Pet->newEntity(['name'=>'Pet' . $i]));
        }
        

     
        $controller = new TestsController(
            new Request('pets/edit/2048'),
            new Response()
        );
        $controller->Paginator = new MockPaginator();
       
    
        $results = $controller->paginate();
        $this->assertEquals(20, count($results));

        $results = $controller->paginate('Pet', ['limit'=>10]);
        $this->assertEquals(10, count($results));
    }
}
