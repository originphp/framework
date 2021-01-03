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

namespace Origin\Test\Core;

use Origin\Core\BaseObject;
use Origin\Http\Controller\Controller;

class ValueObject extends BaseObject
{
    protected $controller;
    public function initialize(Controller $controller)
    {
        $this->controller = $controller;
    }
    public function controller()
    {
        return $this->controller;
    }
}

class BaseObjectTest extends \PHPUnit\Framework\TestCase
{
    public function testDi()
    {
        $controller = new Controller();
        $simpleObject = new ValueObject($controller);
        $this->assertInstanceOf(Controller::class, $simpleObject->controller());
    }
}
