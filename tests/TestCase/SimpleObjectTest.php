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

namespace Origin\Test;

use Origin\SimpleObject;
use Origin\Controller\Controller;

class ValueObject extends SimpleObject
{
    public $controller;
    public function initialize(Controller $controller)
    {
        $this->controller = $controller;
    }
}

class SimpleObjectTest extends \PHPUnit\Framework\TestCase
{
    public function testDi()
    {
        $controller = new Controller();
        $simpleObject = new ValueObject($controller);
        $this->assertInstanceOf(Controller::class, $simpleObject->controller);
    }
}
