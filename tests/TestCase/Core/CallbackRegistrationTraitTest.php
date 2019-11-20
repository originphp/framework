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

class CallbackRegistry
{
    use CallbackRegistrationTrait;

    public function add($callback, $method, $options = [])
    {
        $this->registerCallback($callback, $method, $options);
    }
    public function enable(string $method)
    {
        return $this->enableCallback($method);
    }
    public function disable(string $method)
    {
        return $this->disableCallback($method);
    }
    public function get($callback)
    {
        return $this->registeredCallbacks($callback);
    }

    public function enabled($callback)
    {
        return $this->registeredCallbacks($callback);
    }
}

class CallbackRegistrationTest extends \PHPUnit\Framework\TestCase
{
    public function testAddCallback()
    {
        $callback = new CallbackRegistry();

        $callback->add('beforeFind', 'doSomething', ['foo' => 'bar']);
        $this->assertEquals(['doSomething' => ['foo' => 'bar']], $callback->get('beforeFind'));
    }

    public function testDisableCallback()
    {
        $callback = new CallbackRegistry();
        $callback->add('beforeFind', 'doSomething', ['foo' => 'bar']);

        $this->assertTrue($callback->disable('doSomething'));
        $this->assertFalse($callback->disable('doSomethingElse'));
    }

    public function testEnableCallback()
    {
        $callback = new CallbackRegistry();
        $callback->add('beforeFind', 'doSomething', ['foo' => 'bar']);

        $this->assertTrue($callback->disable('doSomething'));
        $this->assertTrue($callback->enable('doSomething'));
        $this->assertFalse($callback->enable('doSomething'));
    }

    public function testEnabledCallbacks()
    {
        $callback = new CallbackRegistry();
        $callback->add('beforeFind', 'doSomething');
        $callback->add('beforeFind', 'doSomethingElse');
        $methods = array_keys($callback->enabled('beforeFind'));
        $this->assertEquals(['doSomething','doSomethingElse'], $methods);

        $callback->disable('doSomethingElse');
        $methods = array_keys($callback->enabled('beforeFind'));
        $this->assertEquals(['doSomething'], $methods);
    }
}
