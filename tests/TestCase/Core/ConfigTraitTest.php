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

use Origin\Core\ConfigTrait;

class MockObject
{
    use ConfigTrait;

    protected $defaultConfig = [
        'setting' => 'on'
    ];
}

class ConfigTraitTest extends \PHPUnit\Framework\TestCase
{
    public function testConfig()
    {
        $mock = new MockObject();
        $this->assertEquals(['setting'=>'on'], $mock->config());
        $this->assertEquals('on', $mock->config('setting'));
     
        $mock->config('key', 'value');
        $this->assertEquals('value', $mock->config('key'));

        $mock->config(['foo'=>'bar']);
        $this->assertEquals('bar', $mock->config('foo'));
        $this->assertNull($mock->config('bar'));
    }

    public function testSetGetConfig()
    {
        $mock = new MockObject();
        $this->assertEquals('on', $mock->config('setting')); // default setting (after get)

        $mock = new MockObject();
        $mock->config('foo', 'bar');
        $mock->config(['foo/bar'=>'bar/foo']);

        $this->assertEquals('on', $mock->config('setting')); // default setting after set
        $this->assertTrue($mock->config('settingz', 'none'));
        $this->assertEquals('bar', $mock->config('foo'));
        $this->assertEquals('bar/foo', $mock->config('foo/bar'));

        $mock->config('foo', null);
        $config = $mock->config();
        $this->assertArrayNotHasKey('foo', $config);
    }
}
