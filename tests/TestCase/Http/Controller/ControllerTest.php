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

namespace Origin\Test\Http\Controller;

use Origin\Http\Router;
use Origin\Model\Model;
use Origin\Http\Request;
use Origin\Model\Entity;
use Origin\Http\Response;
use Origin\Model\ModelRegistry;
use Origin\Http\View\Helper\Helper;
use Origin\Model\ConnectionManager;
use Origin\Http\Controller\Controller;
use Origin\Http\Controller\Component\Component;
use Origin\Model\Exception\MissingModelException;
use Origin\Http\Controller\Component\ComponentRegistry;
use Origin\Http\Controller\Component\Exception\MissingComponentException;
use Origin\TestSuite\TestTrait;

class Pet extends Model
{
    protected $connection = 'test';
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
    use TestTrait;
    public $initialized = false;

    public function action()
    {
    }

    public function initialize() : void
    {
        $this->initialized = true;
    }

    /**
    * Callback before the action in the controller is called.
    *
    * @return \Origin\Http\Response|null
    */
    public function startup()
    {
        return null;
    }

    /**
     * Called after the controller action and the component shutdown function.
     * Remember to call parent
     *
     * @return \Origin\Http\Response|null
     */
    public function shutdown()
    {
        return null;
    }

    public function setMockRegistry($registry)
    {
        $this->componentRegistry = $registry;
    }
    public function publicMethod()
    {
    }
    protected function protectedMethod()
    {
    }
    private function privateMethod()
    {
    }

    public function setPaginate(array $data)
    {
        $this->paginate = $data;
    }
}

/**
 * Test afterAction return response
 */
class ApplesController extends Controller
{
    use TestTrait;

    protected $when = null;

    public function action()
    {
        $this->autoRender = false;
    }

    public function initialize() : void
    {
        $this->loadComponent('Fruit', ['className' => FruitComponent::class]);
    }
    public function setWhen($when)
    {
        $this->when = $when;
    }
    public function startup()
    {
        if ($this->when === 'startup') {
            return $this->response;
        }
    }
    public function shutdown()
    {
        if ($this->when === 'shutdown') {
            return $this->response;
        }
    }
}
class FruitComponent extends Component
{
    protected $when = null;

    public function setWhen($when)
    {
        $this->when = $when;
    }
    public function startup()
    {
        if ($this->when === 'startup') {
            return $this->controller()->response();
        }
    }
    public function shutdown()
    {
        if ($this->when === 'shutdown') {
            return $this->controller()->response();
        }
    }
}

class ControllerTest extends \PHPUnit\Framework\TestCase
{
    protected $controller = null;

    protected function setUp(): void
    {
        Router::add('/:controller/:action/*');
        $request = new Request('articles/store/100');
        $response = new Response();
        $this->controller = new Controller($request, $response);
    }

    public function testConstruct()
    {
        $request = new Request('tests/view/512');
        $response = new Response();
        $controller = new TestsController($request, $response);

        $this->assertEquals($request, $controller->request());
        $this->assertEquals($response, $controller->response());

        $this->assertNotEmpty($controller->componentRegistry());
        $this->assertInstanceOf('Origin\Http\Controller\Component\ComponentRegistry', $controller->componentRegistry());
        $this->assertEquals('Tests', $controller->name());
        $this->assertEquals('Test', $controller->getProperty('modelName'));

        $this->assertTrue($controller->initialized);
    }

    public function testLoadComponent()
    {
        $request = new Request('tests/view/512');
        $controller = new TestsController($request, new Response());

        $controller->loadComponent('Tester', ['className' => 'Origin\Test\Http\Controller\TesterComponent']);
        $this->assertObjectHasAttribute('Tester', $controller);
        $this->assertInstanceOf('Origin\Test\Http\Controller\TesterComponent', $controller->Tester);

        $this->expectException(MissingComponentException::class);
        $controller->loadComponent('Tron');
    }

    public function testLoadHelper()
    {
        $request = new Request('tests/view/512');
        $controller = new TestsController($request, new Response());
        $controller->loadHelper('Tester', ['className' => 'Origin\Test\Http\Controller\TesterHelper']);
        $this->assertArrayHasKey('Tester', $controller->getProperty('viewHelpers'));
    }

    public function testSet()
    {
        $request = new Request('tests/edit/1024');
        $controller = new TestsController($request, new Response());

        $apples = ['granny smith', 'pink lady'];
        $controller->set('apples', $apples);
        $this->assertArrayHasKey('apples', $controller->viewVars());
        $this->assertEquals($controller->viewVars()['apples'], $apples);

        $fruits = ['apple', 'banana', 'orange'];
        $controller->set('fruits', $fruits);
        $this->assertArrayHasKey('fruits', $controller->viewVars());
        $this->assertEquals($controller->viewVars()['fruits'], $fruits);

        $combo = [
            'apples' => ['granny smith', 'pink lady'],
            'bananas' => ['cavendish', 'Baby (Niño)'],
            'oranges' => ['blood', 'clementine'],
        ];
        $controller->set($combo);
        $this->assertArrayHasKey('bananas', $controller->viewVars());
        $this->assertEquals(['granny smith', 'pink lady'], $controller->viewVars()['apples']);
    }

    public function testIsAccessible()
    {
        $request = new Request('tests/edit/2048');
        $controller = new TestsController($request, new Response());
        $this->assertTrue($controller->callMethod('isAccessible', ['publicMethod']));
        $this->assertTrue($controller->callMethod('isAccessible', ['initialize']));
        $this->assertFalse($controller->callMethod('isAccessible', ['protectedMethod']));
        $this->assertFalse($controller->callMethod('isAccessible', ['privateMethod']));
        $this->assertFalse($controller->callMethod('isAccessible', ['unkownMethod']));
    }

    public function testLoadModel()
    {
        $request = new Request('tests/edit/2048');
        $controller = new TestsController($request, new Response());
        $model = new Model(['name' => 'Test']);
        ModelRegistry::set('Test', $model);
        $lazyLoaded = $controller->Test;
        $this->assertInstanceOf(Model::class, $lazyLoaded);

        $model = new Model(['name' => 'Tes2t']);
        ModelRegistry::set('Test2', $model);

        $this->assertInstanceOf(Model::class, $controller->loadModel('Test2'));
        $this->assertInstanceOf(Model::class, $controller->Test2);
        $this->assertNull($controller->NonExistantModel);
    }

    public function testLoadModelException()
    {
        $request = new Request('tests/edit/2048');
        $controller = new TestsController($request, new Response());
        $this->expectException(MissingModelException::class);
        $controller->loadModel('Panda');
    }

    public function testCallbacksStartup()
    {
        $request = new Request('tests/edit/2048');

        $controller = $this->getMockBuilder('Origin\Test\Http\Controller\TestsController')
            ->setMethods(['startup','shutdown'])
            ->setConstructorArgs([$request, new Response()])
            ->getMock();

        $controller->expects($this->once())
            ->method('startup');

        $components = $this->getMockBuilder('Origin\Http\Controller\Component\ComponentRegistry')
            ->setMethods(['call'])
            ->setConstructorArgs([$controller])
            ->getMock();

        $components->expects($this->at(0))
            ->method('call')
            ->with('startup');

        $components->expects($this->at(1))
            ->method('call')
            ->with('shutdown');

        $controller->setMockRegistry($components);
        
        $controller->setProperty('autoRender', false);
        $controller->dispatch('action');
    }


    public function testStartupBeforeFilterResponse()
    {
        $request = new Request('tests/edit/2048');
        $controller = new ApplesController($request, new Response());
        $controller->setWhen('startup');
        $this->assertInstanceOf(Response::class, $controller->callMethod('startupProcess'));
    }

    public function testStartupStartupResponse()
    {
        $request = new Request('tests/edit/2048');
        $controller = new ApplesController($request, new Response());
        $controller->Fruit->setWhen('startup');
        $this->assertInstanceOf(Response::class, $controller->callMethod('startupProcess'));
    }

    public function testShutdownAfterFilterResponse()
    {
        $request = new Request('tests/edit/2048');
        $controller = new ApplesController($request, new Response());
        $controller->setWhen('shutdown');
        $this->assertNull($controller->callMethod('startupProcess'));
        $this->assertInstanceOf(Response::class, $controller->callMethod('shutdownProcess'));
    }

    public function testStartupShutdownResponse()
    {
        $request = new Request('tests/edit/2048');
        $controller = new ApplesController($request, new Response());
        $controller->Fruit->setWhen('shutdown');
        $this->assertNull($controller->callMethod('startupProcess'));
        $this->assertInstanceOf(Response::class, $controller->callMethod('shutdownProcess'));
    }

    /**
     * Create tmp view file
     *
     * @return void
     */
    public function testRender()
    {
        $request = new Request('posts/index');
        $controller = new \App\Http\Controller\PostsController($request, new Response());
        $controller->setProperty('layout', false);
        $controller->render();

        $this->assertEquals('<h1>Posts Home Page</h1>', $controller->response()->body());
    }

    public function testRenderSerializeArraysJson()
    {
        // test single
        $request = new Request('posts/index.json');
        $controller = new \App\Http\Controller\PostsController($request, new Response());
        $controller->set(['user' => ['name' => 'jim']]);
        $controller->serialize('user');
        $controller->render();
        $this->assertEquals('{"name":"jim"}', $controller->response()->body());
     
        // Test multi
        $controller = new \App\Http\Controller\PostsController(new Request('posts/index.json'), new Response());
        $controller->set(['user' => ['name' => 'jim'],'profile' => ['name' => 'admin']]);
        $controller->serialize(['user','profile']);
        $controller->render();
  
        $this->assertEquals('{"user":{"name":"jim"},"profile":{"name":"admin"}}', $controller->response()->body());
    }

    public function testRenderSerializeArraysXml()
    {
        // test single
        $request = new Request('posts/index.xml');
        $controller = new \App\Http\Controller\PostsController($request, new Response());
        $controller->set(['user' => ['name' => 'jim']]);
        $controller->serialize('user');
        $controller->render();
        $this->assertEquals("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<response><user><name>jim</name></user></response>\n", $controller->response()->body());

        // Test multi
        $controller = new \App\Http\Controller\PostsController(new Request('posts/index.xml'), new Response());
        $controller->set(['user' => ['name' => 'jim'],'profile' => ['name' => 'admin']]);
        $controller->serialize(['user','profile']);
        $controller->render();
  
        $this->assertEquals("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<response><user><name>jim</name></user><profile><name>admin</name></profile></response>\n", $controller->response()->body());
    }

    public function testRenderJson()
    {
        $request = new Request('tests/edit/1024');
        $controller = new TestsController($request, new Response());
        $data = ['data' => ['game' => 'Dota 2']];
        $controller->render(['json' => $data,'status' => 201]);
        $this->assertEquals(json_encode($data), $controller->response()->body());
        $this->assertEquals(201, $controller->response()->statusCode());
        $this->assertEquals('application/json', $controller->response()->type());

        $controller = new TestsController($request, new Response());
        $book = new Entity();
        $book->name = 'How to use PHPUnit';
        $controller->render(['json' => $book]);
        $this->assertEquals($book->toJson(), $controller->response()->body());

        // test serialize and request
        $controller = new TestsController($request, new Response());
        $book = new Entity();
        $book->name = 'How to use PHPUnit';
        $controller->set('book', $book);
        $controller->serialize('book');
        $request->type('json');
        $controller->render();
        $this->assertEquals($book->toJson(), $controller->response()->body());
    }

    public function testRenderXml()
    {
        $request = new Request('tests/feed');
        $controller = new TestsController($request, new Response());
        
        $data = [
            'book' => [
                'xmlns:' => 'http://www.w3.org/1999/xhtml',
                'title' => 'Its a Wonderful Day',
            ],
        ];

        $xml = $expected = '<?xml version="1.0" encoding="UTF-8"?>' . "\n". '<book xmlns="http://www.w3.org/1999/xhtml"><title>Its a Wonderful Day</title></book>'."\n";
       
        $controller->render(['xml' => $data,'status' => 201]);
        $this->assertEquals($expected, $controller->response()->body());
        $this->assertEquals(201, $controller->response()->statusCode());
        $this->assertEquals('application/xml', $controller->response()->type());

        $controller = new TestsController($request, new Response());
        $controller->render(['xml' => $xml]); //xml string
        $this->assertEquals($xml, $controller->response()->body());

        $controller = new TestsController($request, new Response());
        $book = new Entity();
        $book->name = 'How to use PHPUnit';
        $controller->render(['xml' => $book]);
        $this->assertEquals($book->toXml(), $controller->response()->body());

        // test serialize and request
        $controller = new TestsController($request, new Response());
        $book = new Entity(['name' => 'book']);
        $book->name = 'How to use PHPUnit';
        $controller->set('book', $book);
        $controller->serialize('book');
        $request->type('xml');
        $controller->render();
        $expected = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<response><book><name>How to use PHPUnit</name></book></response>\n";
        $this->assertEquals($expected, $controller->response()->body());
    }

    public function testRenderText()
    {
        $request = new Request('tests/status');
        $controller = new TestsController($request, new Response());
        $controller->render(['text' => 'OK','status' => 201]);
        $this->assertEquals('OK', $controller->response()->body());
        $this->assertEquals(201, $controller->response()->statusCode());
        $this->assertEquals('text/plain', $controller->response()->type());
    }

    public function testRenderFile()
    {
        $request = new Request('tests/status');
        $controller = new TestsController($request, new Response());
        $controller->render(['file' => ROOT . DS .'phpunit.xml.dist','status' => 201]);
        $this->assertEquals(file_get_contents(ROOT . DS .'phpunit.xml.dist'), $controller->response()->body());
        $this->assertEquals(201, $controller->response()->statusCode());
        $this->assertEquals('text/xml', $controller->response()->type());
    }

    public function testRedirect()
    {
        $request = new Request('tests/edit/2048');
        $response = $this->getMockBuilder(Response::class)
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

        $this->assertInstanceOf(Response::class, $controller->redirect([
            'action' => 'view', 2048,
        ]));
    }

    public function testPaginate()
    {
        # Create Table
        $connection = ConnectionManager::get('test');
        $connection->execute('DROP TABLE IF EXISTS pets');
        $options = ['constraints' => ['primary' => ['type' => 'primary', 'column' => 'id']]];
        $statements = $connection->adapter()->createTableSql('pets', [
            'id' => ['type' => 'integer','autoIncrement' => true],
            'name' => ['type' => 'string','limit' => 20],
        ], $options);
        
        foreach ($statements as $statement) {
            $connection->execute($statement);
        }
        # Create Dummy Data
        $Pet = new Pet();
        ModelRegistry::set('Pet', $Pet);
        for ($i = 0;$i < 100;$i++) {
            $Pet->save($Pet->new(['name' => 'Pet' . $i]));
        }
        
        $controller = new TestsController(
            new Request('pets/edit/2048'),
            new Response()
        );
        $controller->Paginator = new MockPaginator();
       
        $controller->Test = $Pet; // Set fake model using real model
        $results = $controller->paginate();
        $this->assertEquals(20, count($results));

        $results = $controller->paginate('Pet', ['limit' => 10]);
        $this->assertEquals(10, count($results));

        $controller->setPaginate(['Pet' => ['limit' => 7]]);
        $results = $controller->paginate('Pet');
        $this->assertEquals(7, count($results));
    }

    public function testRequest()
    {
        $this->assertInstanceOf(Request::class, $this->controller->request());
    }

    public function testResponse()
    {
        $this->assertInstanceOf(Response::class, $this->controller->response());
    }
    public function testComponentRegistry()
    {
        $this->assertInstanceOf(ComponentRegistry::class, $this->controller->componentRegistry());
    }

    protected function tearDown(): void
    {
        $connection = ConnectionManager::get('test');
        $connection->execute('DROP TABLE IF EXISTS pets');
    }
}
