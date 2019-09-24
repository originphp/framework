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

namespace Origin\Test\Concern;

use Origin\Model\Model;
use Origin\Concern\ModelConcern;
use Origin\Controller\Controller;
use Origin\Concern\ControllerConcern;

class MockModel extends Model
{
    public function modelFoo()
    {
        return 'bar';
    }
}
class MockController extends Controller
{
    public function controllerFoo()
    {
        return 'bar';
    }
}
class MyModelConcern extends ModelConcern
{
    public function foo()
    {
        return 'bar';
    }
}
class MyControllerConcern extends ControllerConcern
{
    public function bar()
    {
        return 'foo';
    }
}
class ConcernTest extends \PHPUnit\Framework\TestCase
{
    public function testModel()
    {
        $model = new MockModel(['name' => 'Article','connection' => 'test']);
        $concern = new  MyModelConcern($model);
        $this->assertInstanceOf(Model::class, $concern->model());
    }
    public function testModelCallConcernFromConcern()
    {
        $model = new MockModel(['name' => 'Article','connection' => 'test']);
        $concern = new  MyModelConcern($model);
        $this->assertEquals('bar', $concern->modelFoo());
    }

    public function testModelCallConcernFromModel()
    {
        $model = new MockModel(['name' => 'Article','connection' => 'test']);
        $model->loadConcern('MyModelConcern', ['className' => 'Origin\Test\Concern\MyModelConcern']);
        $this->assertEquals('bar', $model->foo());
    }
    public function testController()
    {
        $controller = new MockController();
        $concern = new  MyControllerConcern($controller);
        $this->assertInstanceOf(Controller::class, $concern->controller());
    }

    public function testControllerCallConcernFromConcern()
    {
        $controller = new MockController();
        $concern = new  MyControllerConcern($controller);
        $this->assertInstanceOf(Controller::class, $concern->controller());
        $this->assertEquals('bar', $concern->controllerFoo());
    }

    public function testControllerCallConcernFromController()
    {
        $controller = new MockController();
        $controller->loadConcern('MyControllerConcern', ['className' => 'Origin\Test\Concern\MyControllerConcern']);
        $this->assertEquals('foo', $controller->bar());
    }
}
