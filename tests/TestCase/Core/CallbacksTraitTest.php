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

use Origin\Core\CallbacksTrait;
use Origin\TestSuite\TestTrait;
use Origin\TestSuite\OriginTestCase;

class CallbackCounter
{
    use CallbacksTrait;
    use TestTrait;

    public $count = 0;

    public function onCount(string $method)
    {
        $this->registerCallback('onCount', $method);
    }

    public function increase(): void
    {
        $this->count ++;
    }
    
    public function decrease(): void
    {
        $this->count --;
    }
}

class CallbacksTraitTest extends OriginTestCase
{
    public function testRegisterCallback()
    {
        $counter = new CallbackCounter();
        $counter->callMethod('registerCallback', ['onCount','increase']);
 
        $this->assertEquals(
            ['increase' => []],
            $counter->callMethod('getCallbacks', ['onCount'])
        );
    }

    /**
     * @depends testRegisterCallback
     */
    public function testDisableCallback()
    {
        $counter = new CallbackCounter();
        $counter->callMethod('registerCallback', ['onCount','increase']);

        $this->assertTrue($counter->callMethod('disableCallback', ['onCount','increase']));
        $this->assertFalse($counter->callMethod('disableCallback', ['onCount','increase']));
    }

    /**
     * @depends testDisableCallback
     */
    public function testEnableCallback()
    {
        $counter = new CallbackCounter();
        $counter->callMethod('registerCallback', ['onCount','increase']);
        $counter->callMethod('disableCallback', ['onCount','increase']);

        $this->assertTrue($counter->callMethod('enableCallback', ['onCount','increase']));
        $this->assertFalse($counter->callMethod('enableCallback', ['onCount','increase']));
    }

    public function testGetCallbacks()
    {
        $counter = new CallbackCounter();
        $this->assertEmpty($counter->callMethod('getCallbacks', ['foo']));

        $counter->callMethod('registerCallback', ['onCount','increase']);
        $this->assertEquals(
            ['increase' => []],
            $counter->callMethod('getCallbacks', ['onCount'])
        );

        $counter->callMethod('disableCallback', ['onCount','increase']);
        $this->assertEmpty($counter->callMethod('getCallbacks', ['foo']));
    }

    public function testDisableCallbackDeprecated()
    {
        $this->deprecated(function () {
            $counter = new CallbackCounter();
            $counter->callMethod('registerCallback', ['onCount','increase']);
            $this->assertTrue($counter->callMethod('disableCallback', ['increase']));
            $this->assertFalse($counter->callMethod('disableCallback', ['increase']));
        });
    }

    public function testEnableCallbackDeprecated()
    {
        $this->deprecated(function () {
            $counter = new CallbackCounter();
            $counter->callMethod('registerCallback', ['onCount','increase']);
            $counter->callMethod('disableCallback', ['onCount','increase']);
            $this->assertTrue($counter->callMethod('enableCallback', ['increase']));
            $this->assertFalse($counter->callMethod('enableCallback', ['increase']));
        });
    }
}
