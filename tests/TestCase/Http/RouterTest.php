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

namespace Origin\Test\Http;

use Origin\Http\Router;
use Origin\Http\Request;

class MockRouter extends Router
{
    public static function reset()
    {
        static::$routes = [];
    }
}
class RouterTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        // Add Default Routes
        MockRouter::add('/:controller/:action/*');
        MockRouter::add('/:controller', array('action' => 'index'));
    }

    public function testParseDefaultRoute()
    {
        $result =MockRouter::parse('leads/index');
        $this->assertEquals('Leads', $result['controller']);
        $this->assertEquals('index', $result['action']);

        $result =MockRouter::parse('user_profiles/index');
        $this->assertEquals('UserProfiles', $result['controller']);
        $this->assertEquals('index', $result['action']);

        $result =MockRouter::parse('leads/edit/1000');
        $this->assertEquals('Leads', $result['controller']);
        $this->assertEquals('edit', $result['action']);
        $this->assertEquals(array(1000), $result['args']);

        $result =MockRouter::parse('user_profiles/view/sort/256');
        $this->assertEquals('UserProfiles', $result['controller']);
        $this->assertEquals('view', $result['action']);
        $this->assertEquals(array('sort', '256'), $result['args']);

        // Parse Named Params
        $result =MockRouter::parse('leads/home/sort:asc/limit:10');
        $this->assertEquals('asc', $result['named']['sort']);
        $this->assertEquals('10', $result['named']['limit']);
    }

    public function testRouteIndex()
    {
        $result = MockRouter::parse('/leads');

        $this->assertEquals('Leads', $result['controller']);
        $this->assertEquals('index', $result['action']);

        $result =MockRouter::parse('/user_profiles');

        $this->assertEquals('UserProfiles', $result['controller']);
        $this->assertEquals('index', $result['action']);
    }

    public function testRoutes()
    {
        $this->assertNotEmpty(MockRouter::routes());
    }

    public function testRouteHome()
    {
        MockRouter::reset();
        MockRouter::add('/', array('controller' => 'pages', 'action' => 'display', 'home'));

        $result =MockRouter::parse('/');

        $this->assertEquals('Pages', $result['controller']);
        $this->assertEquals('display', $result['action']);
        $this->assertEquals(array('home'), $result['args']);
    }

    public function testRoutePage()
    {
        MockRouter::reset();
        MockRouter::add('/help', array('controller' => 'docs', 'action' => 'view', 256));

        $result =MockRouter::parse('/help');

        $this->assertEquals('Docs', $result['controller']);
        $this->assertEquals('view', $result['action']);
        $this->assertEquals(array(256), $result['args']);
    }

    public function testRenameController()
    {
        MockRouter::reset();
        MockRouter::add(
        '/developers/:action/*',
            array('controller' => 'users')
    );
        $result =MockRouter::parse('/developers/directory');

        $this->assertEquals('Users', $result['controller']);
        $this->assertEquals('directory', $result['action']);
    }

    public function testOneController()
    {
        MockRouter::reset();

        MockRouter::add('/:action/*', array('controller' => 'jobs')); // one controller

        $result =MockRouter::parse('/active');

        $this->assertEquals('Jobs', $result['controller']);
        $this->assertEquals('active', $result['action']);
    }

    public function testUrl()
    {
        MockRouter::reset();
        MockRouter::add('/:controller/:action/*');
        MockRouter::setRequest(new Request('articles/view/100'));

        $expected = '/articles/edit/100';
        $this->assertEquals($expected, MockRouter::url($expected));

        $url = array('controller' => 'Articles', 'action' => 'edit', 100);
        $expected = '/articles/edit/100';
        $this->assertEquals($expected, MockRouter::url($url));

        $url = array('controller' => 'UserProfiles', 'action' => 'edit', 256);
        $expected = '/user_profiles/edit/256';
        $this->assertEquals($expected, MockRouter::url($url));

        $url = array('action' => 'edit', 100);
        $expected = '/articles/edit/100';
        $this->assertEquals($expected, MockRouter::url($url));

        $url = array('action' => 'edit', 100, 'other');
        $expected = '/articles/edit/100/other';
        $this->assertEquals($expected, MockRouter::url($url));

        $url = array('action' => 'edit');
        $expected = '/articles/edit';
        $this->assertEquals($expected, MockRouter::url($url));

        $url = array('action' => 'index', 100, '?' => array('page' => 1));
        $expected = '/articles/index/100?page=1';
        $this->assertEquals($expected, MockRouter::url($url));

        $url = array('action' => 'index', 100, '?' => array('page' => 1, 'limit' => 2));
        $expected = '/articles/index/100?page=1&limit=2';
        $this->assertEquals($expected, MockRouter::url($url));

        $url = array('action' => 'something', 'named1' => 'yes', 'named2' => 'no');
        $expected = '/articles/something/named1:yes/named2:no';
        $this->assertEquals($expected, MockRouter::url($url));

        $url = array('action' => 'edit', 100, '#' => 'top');
        $expected = '/articles/edit/100#top';
        $this->assertEquals($expected, MockRouter::url($url));

        $url = array('action' => 'index', 'plugin'=>'contact_manager');
        $expected = '/contact_manager/articles/index';
        $this->assertEquals($expected, MockRouter::url($url));
    }
}
