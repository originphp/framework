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

class FunctionsTest extends \PHPUnit\Framework\TestCase
{
    public function testPluginSplit()
    {
        list($plugin, $name) = pluginSplit('ContactManager.contacts');
        $this->assertEquals('ContactManager', $plugin);
        $this->assertEquals('contacts', $name);
    }

    public function testNamespaceSplit()
    {
        list($namespace, $classname) = namespaceSplit('Origin\Framework\Dispatcher');
        $this->assertEquals('Origin\Framework', $namespace);
        $this->assertEquals('Dispatcher', $classname);
    }

    public function testTranslate()
    {
        $null = null;
        $this->assertNull(__($null));
        $expected = 'Nothing';
        $translate = __($expected); // no translation return as is
        $this->assertEquals('Nothing', $translate);

        $translated = __('Your password is %s!', 'secret');
        $this->assertEquals('Your password is secret!', $translated);
        
        $translated = __('Your username is %email% and your password is %password%.', ['%email%'=>'jimbo@example.com', '%password%'=>'secret']);
        $this->assertEquals('Your username is jimbo@example.com and your password is secret.', $translated);
    }
    public function testH()
    {
        $this->assertEquals('&lt;h1&gt;Headline&lt;/h1&gt;', h('<h1>Headline</h1>'));
    }
    public function testNow()
    {
        $this->assertEquals(date('Y-m-d H:i:s'), now());
    }
    public function testUUID()
    {
        $this->assertRegExp(
            '/\b[0-9a-f]{8}\b-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-\b[0-9a-f]{12}\b/',
            uuid()
        );
    }
    public function testUid()
    {
        $this->assertRegExp('/^([a-z0-9]*){13}$/', uid());
        $this->assertRegExp('/^([a-z0-9]*){5}$/', uid(5));
    }
}
