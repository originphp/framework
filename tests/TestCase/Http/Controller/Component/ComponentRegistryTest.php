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

namespace Origin\Test\Http\Controller\Component;

use Origin\Http\Request;
use Origin\Http\Response;
use Origin\Http\Controller\Controller;
use Origin\Http\Controller\Component\Component;
use Origin\Http\Controller\Component\ComponentRegistry;
use Origin\Http\Controller\Component\Exception\MissingComponentException;

class MyComponent extends Component
{
    public function startup()
    {
        return new Response(); // same as redirect
    }
}

class ComponentRegistryTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->Controller = new Controller(new Request(), new Response());
    }
    public function testLoad()
    {
        $componentRegistry = new ComponentRegistry($this->Controller);
        $component = $componentRegistry->load('Component');
        $this->assertInstanceOf(Component::class, $component);
    }

    public function testThrowException()
    {
        $this->expectException(MissingComponentException::class);
        $componentRegistry = new ComponentRegistry($this->Controller);
        $componentRegistry->load('ComponentThatDoesNotExist');
    }

    public function testController()
    {
        $componentRegistry = new ComponentRegistry($this->Controller);
        $this->assertInstanceOf('Origin\Http\Controller\Controller', $componentRegistry->controller());
    }

    /**
     * Need to reach
     *
     * @return void
     */
    public function testCall()
    {
        $componentRegistry = new ComponentRegistry($this->Controller);
        $component = $componentRegistry->load(MyComponent::class);
        $this->assertInstanceOf(MyComponent::class, $component);
        $result = $componentRegistry->call('startup');
        $this->assertInstanceOf(Response::class, $result);
    }
}
