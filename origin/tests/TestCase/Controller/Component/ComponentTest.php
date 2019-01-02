<?php
/**
 * OriginPHP Framework
 * Copyright 2018 Jamiel Sharief.
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

use Origin\Controller\Component\Component;
use Origin\Controller\Component\ComponentRegistry;
use Origin\Controller\Controller;
use Origing\Controller\Component\AuthComponent;

class MockComponent extends Component
{
    public function getComponents()
    {
        return $this->_components;
    }
}

class ComponentTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        $Controller = new Controller();
        $this->MockComponent = new MockComponent($Controller);
    }

    public function testConstruct()
    {
        $this->assertInstanceOf(
            'Origin\Controller\Component\ComponentRegistry',
            $this->MockComponent->componentRegistry()
        );
    }

    public function testLoadComponents()
    {
        $MockComponent = $this->MockComponent;
        $MockComponent->loadComponents([
            'Apple',
            'Orange' => ['type'=>'Fruit']
        ]);
        
        $expected = [
            'Apple' => ['className'=>'AppleComponent'],
            'Orange' => ['className'=>'OrangeComponent','type'=>'Fruit'],
        ];

        $this->assertEquals($expected, $MockComponent->getComponents());
    }

    public function testController()
    {
        $this->assertInstanceOf('Origin\Controller\Controller', $this->MockComponent->controller());
    }

    /**
     * @depends testLoadComponents
     */
    public function testLoading()
    {
        $MockComponent = $this->MockComponent;
        $MockComponent->loadComponent('Auth');
  
        $this->assertInstanceOf('Origin\Controller\Component\AuthComponent', $MockComponent->Auth);
    }
}
