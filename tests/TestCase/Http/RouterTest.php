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

use Origin\Http\Router;
use Origin\Http\Request;

class MockRouter extends Router
{
    public static function reset()
    {
        static::$routes = [];
    }
    public static function setRoutes(array $routes)
    {
        static::$routes = $routes;
    }
}
class RouterTest extends \PHPUnit\Framework\TestCase
{
    protected static $routes = [];

    public static function setUpBeforeClass(): void
    {
        static::$routes = Router::routes();
        MockRouter::reset();
    }

    public static function tearDownAfterClass(): void
    {
        MockRouter::reset();

        MockRouter::setRoutes(static::$routes);
    }

    protected function tearDown(): void
    {
        MockRouter::reset();
    }

    public function testParseDefaultRoute()
    {
        MockRouter::add('/:controller/:action/*');
        MockRouter::add('/:controller', ['action' => 'index']);

        $result = MockRouter::parse('leads/index');
        $this->assertEquals('Leads', $result['controller']);
        $this->assertEquals('index', $result['action']);

        $result = MockRouter::parse('user_profiles/index');
        $this->assertEquals('UserProfiles', $result['controller']);
        $this->assertEquals('index', $result['action']);

        $result = MockRouter::parse('leads/edit/1000');
        $this->assertEquals('Leads', $result['controller']);
        $this->assertEquals('edit', $result['action']);
        $this->assertEquals([1000], $result['args']);

        $result = MockRouter::parse('user_profiles/view/sort/256');
        $this->assertEquals('UserProfiles', $result['controller']);
        $this->assertEquals('view', $result['action']);
        $this->assertEquals(['sort', '256'], $result['args']);

        // Parse Named Params
        $result = MockRouter::parse('leads/home/sort:asc/limit:10');
        $this->assertEquals('asc', $result['named']['sort']);
        $this->assertEquals('10', $result['named']['limit']);
    }

    public function testGreedyArgs()
    {
        MockRouter::add('/posts/custom/*', ['controller' => 'Articles', 'action' => 'show', 'all']);

        $result = MockRouter::parse('/posts/custom');
        $this->assertEquals('all', $result['args'][0]);
        $result = MockRouter::parse('/posts/custom/1234');
        $this->assertEquals('all', $result['args'][0]);
        $this->assertEquals('1234', $result['args'][1]);
    }

    public function testComplicated()
    {
        MockRouter::add('/', ['controller' => 'pages', 'action' => 'display', 'home']);
        MockRouter::add('/t/*', ['controller' => 'Topics','action' => 'view']);
        MockRouter::add('/:controller/:action/*');
        MockRouter::add('/:controller', ['action' => 'index']);

        $result = MockRouter::parse('topics');
        $this->assertEquals('Topics', $result['controller']);
        $this->assertEquals('index', $result['action']);
        $result = MockRouter::parse('topics/add');
        $this->assertEquals('Topics', $result['controller']);
        $this->assertEquals('add', $result['action']);
        $result = MockRouter::parse('topics/add/1234');
        $this->assertEquals('Topics', $result['controller']);
        $this->assertEquals('add', $result['action']);

        $result = MockRouter::parse('t/some-slug/12345');
        $this->assertEquals('Topics', $result['controller']);
        $this->assertEquals('view', $result['action']);
    }

    public function testRouteIndex()
    {
        MockRouter::add('/:controller/:action/*');
        MockRouter::add('/:controller', ['action' => 'index']);

        $result = MockRouter::parse('/leads');

        $this->assertEquals('Leads', $result['controller']);
        $this->assertEquals('index', $result['action']);

        $result = MockRouter::parse('/user_profiles');

        $this->assertEquals('UserProfiles', $result['controller']);
        $this->assertEquals('index', $result['action']);
    }

    public function testRoutes()
    {
        MockRouter::add('/:controller/:action/*');
       
        $this->assertNotEmpty(MockRouter::routes());
    }

    public function testRouteHome()
    {
        MockRouter::add('/', ['controller' => 'pages', 'action' => 'display', 'home']);

        $result = MockRouter::parse('/');

        $this->assertEquals('Pages', $result['controller']);
        $this->assertEquals('display', $result['action']);
        $this->assertEquals(['home'], $result['args']);
    }

    public function testRouterNoMatchFound()
    {
        $this->assertEquals([], MockRouter::parse('/foo'));
    }

    public function testRoutePage()
    {
        MockRouter::add('/help', ['controller' => 'docs', 'action' => 'view', 256]);

        $result = MockRouter::parse('/help');

        $this->assertEquals('Docs', $result['controller']);
        $this->assertEquals('view', $result['action']);
        $this->assertEquals([256], $result['args']);
    }

    public function testRenameController()
    {
        MockRouter::add(
            '/developers/:action/*',
            ['controller' => 'users']
        );
        $result = MockRouter::parse('/developers/directory');

        $this->assertEquals('Users', $result['controller']);
        $this->assertEquals('directory', $result['action']);
    }

    public function testOneController()
    {
        MockRouter::add('/:action/*', ['controller' => 'jobs']); // one controller

        $result = MockRouter::parse('/active');

        $this->assertEquals('Jobs', $result['controller']);
        $this->assertEquals('active', $result['action']);
    }

    public function testUrl()
    {
        MockRouter::add('/:controller/:action/*');
        MockRouter::request(new Request('articles/view/100'));

        $expected = '/articles/edit/100';
        $this->assertEquals($expected, MockRouter::url($expected));

        $url = ['controller' => 'Articles', 'action' => 'edit', 100];
        $expected = '/articles/edit/100';
        $this->assertEquals($expected, MockRouter::url($url));

        $url = ['controller' => 'UserProfiles', 'action' => 'edit', 256];
        $expected = '/user_profiles/edit/256';
        $this->assertEquals($expected, MockRouter::url($url));

        $url = ['action' => 'edit', 100];
        $expected = '/articles/edit/100';
        $this->assertEquals($expected, MockRouter::url($url));

        $url = ['action' => 'edit', 100, 'other'];
        $expected = '/articles/edit/100/other';
        $this->assertEquals($expected, MockRouter::url($url));

        $url = ['action' => 'edit'];
        $expected = '/articles/edit';
        $this->assertEquals($expected, MockRouter::url($url));

        $url = ['action' => 'index', 100, '?' => ['page' => 1]];
        $expected = '/articles/index/100?page=1';
        $this->assertEquals($expected, MockRouter::url($url));

        $url = ['action' => 'index', 100, '?' => ['page' => 1, 'limit' => 2]];
        $expected = '/articles/index/100?page=1&limit=2';
        $this->assertEquals($expected, MockRouter::url($url));

        $url = ['action' => 'something', 'named1' => 'yes', 'named2' => 'no'];
        $expected = '/articles/something/named1:yes/named2:no';
        $this->assertEquals($expected, MockRouter::url($url));

        $url = ['action' => 'edit', 100, '#' => 'top'];
        $expected = '/articles/edit/100#top';
        $this->assertEquals($expected, MockRouter::url($url));

        $url = ['action' => 'index', 'plugin' => 'contact_manager'];
        $expected = '/contact_manager/articles/index';
        $this->assertEquals($expected, MockRouter::url($url));

        $url = ['action' => 'index', 100, 'ext' => 'json'];
        $expected = '/articles/index/100.json';
        $this->assertEquals($expected, MockRouter::url($url));

        $url = ['action' => 'index', 100, 'ext' => 'json', '?' => ['page' => 1]];
        $expected = '/articles/index/100.json?page=1';
        $this->assertEquals($expected, MockRouter::url($url));
    }

    public function testExtensions()
    {
        $this->assertEquals(['json','xml'], Router::extensions());
        Router::extensions(['xml']);
        $this->assertEquals(['xml'], Router::extensions());
        Router::extensions(['json','xml']); // put back
    }

    public function testPlugin()
    {
        MockRouter::add('/contact_manager/:controller/:action', ['action' => 'index','plugin' => 'ContactManager']);
       
        MockRouter::request(new Request('/contact_manager/contacts/add'));
        
        $route = MockRouter::parse('/contact_manager/contacts/add');
        $this->assertEquals('Contacts', $route['controller']);
        $this->assertEquals('ContactManager', $route['plugin']);

        // check plugin is added to the URL, this is used by redirect & html::link
        $this->assertEquals('/contact_manager/contacts/view', MockRouter::url(['action' => 'view']));
        $this->assertEquals('/contacts/view', MockRouter::url(['action' => 'view','plugin' => false]));
    }

    public function testPrefix()
    {
        MockRouter::add('/admin/:controller/:action', ['prefix' => 'admin']);
     
        MockRouter::request(new Request('/admin/users/edit'));
        
        $route = MockRouter::parse('/admin/users/edit');

        $this->assertEquals('Users', $route['controller']);
        $this->assertEquals('admin', $route['prefix']);
       
        // check plugin is added to the URL, this is used by redirect & html::link
        $this->assertEquals('/admin/users/view', MockRouter::url(['action' => 'view']));
        $this->assertEquals('/users/view', MockRouter::url(['action' => 'view','prefix' => false]));
    }
}
