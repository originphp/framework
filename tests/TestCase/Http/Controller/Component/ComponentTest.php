<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2021 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Test\Http\Controller\Component;

use Origin\Http\Request;
use Origin\Http\Response;
use Origin\Http\Controller\Controller;
use Origin\Http\Controller\Component\Component;
use Origin\Http\Controller\Component\SessionComponent;

class MockComponent extends Component
{
    public function getComponents()
    {
        return $this->_components;
    }
}

class ComponentTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $Controller = new Controller(new Request(), new Response());
        $this->MockComponent = new MockComponent($Controller);
    }

    public function testGet()
    {
        $this->assertNull($this->MockComponent->Session);
        $this->MockComponent->loadComponent('Session');
        $this->assertInstanceOf(SessionComponent::class, $this->MockComponent->Session);
    }

    public function testController()
    {
        $this->assertInstanceOf(Controller::class, $this->MockComponent->controller());
    }

    public function testRequest()
    {
        $this->assertInstanceOf(Request::class, $this->MockComponent->request());
    }

    public function testResponse()
    {
        $this->assertInstanceOf(Response::class, $this->MockComponent->response());
    }
}
