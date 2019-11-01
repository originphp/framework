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

namespace Origin\Test\Core;

use Origin\Core\CallbackRegistrationTrait;
use Origin\Model\Model;

class Foo extends Model
{
}


class CallbackRegistrationTraitTest extends \PHPUnit\Framework\TestCase
{
    public function testRegisteredCallbacks()
    {
        $foo = new Foo();
        $foo->beforeFind('doSomething');
        $foo->afterFind('doSomethingElse');

        $callbacks = $foo->registeredCallbacks();
        $this->assertArrayHasKey('doSomething', $callbacks['beforeFind']);
        $this->assertArrayHasKey('doSomethingElse', $callbacks['afterFind']);
    }

    public function testDisableEnableCallback()
    {
        $foo = new Foo();
        $foo->beforeFind('doSomething');

        $this->assertTrue($foo->disableCallback('doSomething'));
        $this->assertFalse($foo->disableCallback('doSomethingElse'));
    }

    public function testEnableCallback()
    {
        $foo = new Foo();
        $foo->beforeFind('doSomething');

        $this->assertTrue($foo->disableCallback('doSomething'));
        $this->assertTrue($foo->enableCallback('doSomething'));
        $this->assertFalse($foo->enableCallback('doSomething'));
    }
}
