<?php
/**
 * OriginPHP Framework
 * Copyright 2018 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright     Copyright (c) Jamiel Sharief
 *
 * @link          https://www.originphp.com
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Test\Controller\Component;

use Origin\TestSuite\TestTrait;
use Origin\Controller\Component\AuthComponent;
use Origin\Controller\Controller;
use Origin\Controller\Request;
use Origin\Controller\Response;
use Origin\Core\Session;

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

    public function index()
    {
    }
}

class AuthComponentTest extends \PHPUnit\Framework\TestCase
{
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
        $this->assertFalse($AuthComponent->callMethod('isLoggedIn'));
        Session::write('Auth.User', ['user_name' => 'james']);
        $this->assertTrue($AuthComponent->callMethod('isLoggedIn'));
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
        Session::write('Auth.User', ['user_name' => 'james']);
        $this->assertTrue($AuthComponent->callMethod('isLoggedIn'));

        $this->assertEquals('/users/login', $AuthComponent->logout());
        $this->assertFalse($AuthComponent->callMethod('isLoggedIn'));
        // relogin
        Session::write('Auth.User', ['user_name' => 'james']);
        $newConfig = ['controller' => 'users', 'action' => 'bye'];
        $AuthComponent->config(['logoutRedirect' => $newConfig]);
        $this->assertEquals('/users/bye', $AuthComponent->logout());
    }

    public function testGetCredentials()
    {
        $request = new Request('/users/login');
        $request->data = ['user_name' => 'claire', 'passwd' => 'secret'];

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

    public function testUnauthorize()
    {
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testLoadUser()
    {
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testRedirectUrl()
    {
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }
}
