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

use Origin\Controller\Controller;
use Origin\Controller\Component\Component;
use Origin\Controller\Component\ComponentRegistry;
use Origin\Controller\Component\Exception\MissingComponentException;
use Origin\Controller\Request;
use Origin\Controller\Response;

class ComponentRegistryTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
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
        $this->assertInstanceOf('Origin\Controller\Controller', $componentRegistry->controller());
    }
}
