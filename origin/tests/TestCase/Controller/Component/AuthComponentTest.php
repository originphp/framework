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

namespace Origin\Test\Controller\Component;

use Origin\TestSuite\TestTrait;
use Origin\Controller\Component\AuthComponent;
use Origin\Controller\Controller;
use Origin\Controller\Request;
use Origin\Controller\Response;
use Origin\Core\Session;
use Origin\Model\Entity;
use Origin\TestSuite\OriginTestCase;
use Origin\Exception\ForbiddenException;
use Origin\Model\Exception\MissingModelException;

class MockAuthComponent extends AuthComponent
{
    use TestTrait;
}
class UsersController extends Controller
{
    public function initialize()
    {
        $this->loadComponent('Auth');
    }

    private function secret()
    {
    }

    public function index()
    {
    }

    public function login()
    {
    }
    public function redirect($url, int $code = 302)
    {
        return true;
    }
}

class AuthComponentTest extends OriginTestCase
{
    public $fixtures = ['Framework.User'];
    public function setUp()
    {
        $request = new Request('/users/login');
        $this->Controller = new UsersController($request, new Response());
        $this->AuthComponent = new MockAuthComponent($this->Controller);
    }

    public function testHashPassword()
    {
        $AuthComponent = $this->AuthComponent;
        $result = $AuthComponent->hashPassword('secret');
        $this->assertContains('$2y$10', $result);
    }

    /**
     * @depends testHashPassword
     */
    public function testVerifyPassword()
    {
        $AuthComponent = $this->AuthComponent;
        $result = $AuthComponent->hashPassword('secret');
        $this->assertTrue($AuthComponent->verifyPassword('secret', $result));
    }

    public function testAllow()
    {
        $AuthComponent = $this->AuthComponent;
        $AuthComponent->allow(['index']);
        $this->assertContains('index', $AuthComponent->getProperty('allowedActions'));
        $AuthComponent->allow(['add', 'edit']);
        $this->assertContains('index', $AuthComponent->getProperty('allowedActions'));
        $this->assertContains('add', $AuthComponent->getProperty('allowedActions'));
        $this->assertContains('edit', $AuthComponent->getProperty('allowedActions'));
    }

    /**
     * @depends testAllow
     */
    public function testIsAllowed()
    {
        $AuthComponent = $this->AuthComponent;
        $result = $AuthComponent->callMethod('isAllowed', ['index']);
        $this->assertFalse($result);

        $AuthComponent->allow(['index']);
        $result = $AuthComponent->callMethod('isAllowed', ['index']);
        $this->assertTrue($result);
    }

    public function testIsLoginPage()
    {
        $request = new Request('/users/login');
        $Controller = new UsersController($request, new Response());
        $AuthComponent = new MockAuthComponent($Controller);

        $this->assertTrue($AuthComponent->callMethod('isLoginPage'));
        // Change Config
        $newConfig['loginAction'] = ['controller' => 'Members', 'action' => 'login'];
        $AuthComponent->config($newConfig);
        $this->assertFalse($AuthComponent->callMethod('isLoginPage'));

        // Change Request
        $request = new Request('/articles/index');
        $Controller = new UsersController($request, new Response());
        $AuthComponent = new MockAuthComponent($Controller);
        $this->assertFalse($AuthComponent->callMethod('isLoginPage'));
    }

    public function testIsLoggedIn()
    {
        $AuthComponent = $this->AuthComponent;
        $this->assertFalse($AuthComponent->isLoggedIn());
        $AuthComponent->Session->write('Auth.User', ['user_name' => 'james']);
        $this->assertTrue($AuthComponent->isLoggedIn());
    }

    public function testIsPrivateOrProtected()
    {
        $AuthComponent = $this->AuthComponent;
        $this->assertTrue($AuthComponent->callMethod('IsPrivateOrProtected', ['initialize']));
        $this->assertFalse($AuthComponent->callMethod('IsPrivateOrProtected', ['index']));
    }

    /**
     * depends testIsLoggedIn.
     */
    public function testLogout()
    {
        $AuthComponent = $this->AuthComponent;
        $AuthComponent->Session->write('Auth.User', ['user_name' => 'james']);
        $this->assertTrue($AuthComponent->callMethod('isLoggedIn'));

        $this->assertEquals('/users/login', $AuthComponent->logout());
        $this->assertFalse($AuthComponent->callMethod('isLoggedIn'));
        // relogin
        $AuthComponent->Session->write('Auth.User', ['user_name' => 'james']);
        $newConfig = ['controller' => 'users', 'action' => 'bye'];
        $AuthComponent->config(['logoutRedirect' => $newConfig]);
        $this->assertEquals('/users/bye', $AuthComponent->logout());
    }

    public function testGetCredentials()
    {
        $request = new Request('/users/login');
        $request->data('user_name', 'claire');
        $request->data('passwd', 'secret');
        $Controller = new UsersController($request, new Response());
        $AuthComponent = new MockAuthComponent($Controller);

        $AuthComponent->config(['fields' => ['username' => 'user_name', 'password' => 'passwd']]);

        $expected = ['username' => 'claire', 'password' => 'secret'];
        $this->assertEquals($expected, $AuthComponent->callMethod('getCredentials'));

        $AuthComponent = $this->AuthComponent;
        $AuthComponent->config(['authenticate' => ['Http']]);

        $_SERVER['PHP_AUTH_USER'] = 'amanda';
        $_SERVER['PHP_AUTH_PW'] = 'amanDa1';

        $expected = ['username' => 'amanda', 'password' => 'amanDa1'];
        $this->assertEquals($expected, $AuthComponent->callMethod('getCredentials'));
    }

    public function testSetUser()
    {
        $data = ['username'=>'fred@smith.com','password'=>1234];
        $entity = new Entity($data);
        $this->AuthComponent->setUser($entity);
   
        $this->assertEquals($data, $this->AuthComponent->Session->read('Auth.User'));
    }

    /**
     * @depends testSetUser
     *
     * @return void
     */
    public function testUser()
    {
        $data = ['username'=>'fred@smith.com','password'=>1234,'date'=>'2019-02-07'];
        $entity = new Entity($data);
        $this->AuthComponent->setUser($entity);
        $this->assertEquals($data, $this->AuthComponent->user());
        $this->assertEquals('fred@smith.com', $this->AuthComponent->user('username'));
        $this->assertNull($this->AuthComponent->user('foo'));
        $session = $this->AuthComponent->Session;
        $session->delete('Auth.User');
        $this->assertNull($this->AuthComponent->user('username'));
    }

    public function testAuth()
    {
        $expected = [
            'controller' => 'Users',
        'action' => 'index',
        'plugin' => null];
        $redirectUrl = $this->AuthComponent->redirectUrl();
        $this->assertEquals($expected, $redirectUrl);
        
        $expected = '/dashboard/home';
  
        $this->AuthComponent->Session->write('Auth.redirect', $expected);
        $redirectUrl = $this->AuthComponent->redirectUrl();
        $this->assertEquals($expected, $redirectUrl);
    }

    public function testStartup()
    {
        $request = new Request('/users/index');
        $this->Controller = new UsersController($request, new Response());
        $AuthComponent = new MockAuthComponent($this->Controller);
        $AuthComponent->startup();
        $this->assertEquals('/users/index', $AuthComponent->Session->read('Auth.redirect'));
    }

    public function testStartupNoRedirect()
    {
        $this->expectException(ForbiddenException::class);
        $request = new Request('/users/index');
        $this->Controller = new UsersController($request, new Response());
        $AuthComponent = new MockAuthComponent($this->Controller);
        $AuthComponent->config('unauthorizedRedirect', false);
        $AuthComponent->startup();
    }

    public function testIdentifyNone()
    {
        $AuthComponent = $this->AuthComponent;
        $this->assertFalse($AuthComponent->identify());
    }

    public function testIdentifyForm()
    {
        $AuthComponent = $this->AuthComponent;
        $AuthComponent->config('authenticate', ['Form']);
        $AuthComponent->request()->data('email', 'james@example.com');
        $AuthComponent->request()->data('password', 'secret1');
        
        $result = $AuthComponent->identify();
        $this->assertEquals('James', $result->name);
    }

    public function testIdentifyScope()
    {
        $AuthComponent = $this->AuthComponent;
        $AuthComponent->config('authenticate', ['Form']);
        $AuthComponent->config('scope', ['id'=>1024]);
        $AuthComponent->request()->data('email', 'james@example.com');
        $AuthComponent->request()->data('password', 'secret1');
        $this->assertFalse($AuthComponent->identify());
    }

    public function testIdentifyMissingModel()
    {
        $this->expectException(MissingModelException::class);
        $AuthComponent = $this->AuthComponent;
        $AuthComponent->config('authenticate', ['Form']);
        $AuthComponent->config('model', 'Fozzy');
        $AuthComponent->request()->data('email', 'james@example.com');
        $AuthComponent->request()->data('password', 'secret1');
     
        $AuthComponent->identify();
    }

    public function testIdentifyHttp()
    {
        $AuthComponent = $this->AuthComponent;
        $AuthComponent->config('authenticate', ['Http']);
        $_SERVER['PHP_AUTH_USER'] = 'amanda@example.com';
        $_SERVER['PHP_AUTH_PW'] = 'secret2';
        $result = $AuthComponent->identify();
        $this->assertEquals('Amanda', $result->name);
        unset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
    }

    public function testIdentifyInvalidPassword()
    {
        $AuthComponent = $this->AuthComponent;
        $AuthComponent->config('authenticate', ['Form']);
        $AuthComponent->request()->data('email', 'james@example.com');
        $AuthComponent->request()->data('password', '1234');
        $this->assertFalse($AuthComponent->identify());
    }

    public function testIdentifyUnkownUser()
    {
        $AuthComponent = $this->AuthComponent;
        $AuthComponent->config('authenticate', ['Form']);
        $AuthComponent->request()->data('email', 'mark.ronson@example.com');
        $AuthComponent->request()->data('password', 'funky');
        $this->assertFalse($AuthComponent->identify());
    }

    public function testCheckAuthenticeIsAllowed()
    {
        $request = new Request('/users/index');
        $this->Controller = new UsersController($request, new Response());
        $AuthComponent = new MockAuthComponent($this->Controller);
        $AuthComponent->allow('index');
        $this->assertNull($AuthComponent->startup());
    }

    public function testCheckAuthenticeIsPrivate()
    {
        $request = new Request('/users/secret');
        $this->Controller = new UsersController($request, new Response());
        $AuthComponent = new MockAuthComponent($this->Controller);
        $this->assertNull($AuthComponent->startup());
    }

    public function testCheckAuthenticeIsLoggedIn()
    {
        $session = $this->AuthComponent->Session;
        $session->write('Auth.User', ['user_name' => 'james']);
        $this->assertNull($this->AuthComponent->startup());
        $session->delete('Auth');
    }

    public function testCheckAuthenticeIsLoginPage()
    {
        $request = new Request('/users/login');
        $this->Controller = new UsersController($request, new Response());
        $AuthComponent = new MockAuthComponent($this->Controller);
        $this->assertNull($AuthComponent->startup());
    }
}
