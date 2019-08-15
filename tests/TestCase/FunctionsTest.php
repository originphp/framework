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

        $translated = __('Your password is {password}!', ['password' => 'secret']);
        $this->assertEquals('Your password is secret!', $translated);
        
        $translated = __('Your username is {email} and your password is {password}.', [
            'email' => 'jimbo@example.com',
            'password' => 'secret',
        ]);
        $this->assertEquals('Your username is jimbo@example.com and your password is secret.', $translated);
        $translate = 'You have no apples|You have one apple|You have {count} apples';
        $this->assertEquals('You have no apples', __($translate, ['count' => 0]));
        $this->assertEquals('You have one apple', __($translate, ['count' => 1]));
        $this->assertEquals('You have 2 apples', __($translate, ['count' => 2]));
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

    public function testEnv()
    {
        $this->assertNull(env('ABC', '123'));
        $this->assertEquals('123', env('ABC'));
        $_SERVER['FOO'] = 'bar'; // server info varies
        $this->assertEquals('bar', env('FOO'));
    }

    public function testBegins()
    {
        $this->assertTrue(begins('foo', 'foobar'));
        $this->assertFalse(begins('foo', 'barfoo'));
        $this->assertFalse(begins('', 'barfoo'));
    }
    public function testEnds()
    {
        $this->assertFalse(ends('foo', 'foobar'));
        $this->assertTrue(ends('foo', 'barfoo'));
        $this->assertFalse(ends('', 'barfoo'));
    }
    public function testLeft()
    {
        $this->assertEquals('foo', left(':', 'foo:bar'));
        $this->assertNull(left('x', 'foo:bar'));
        $this->assertNull(left('', ''));
    }
    public function testRight()
    {
        $this->assertEquals('bar', right(':', 'foo:bar'));
        $this->assertNull(right('x', 'foo:bar'));
        $this->assertNull(right('', ''));
    }
    public function testContains()
    {
        $this->assertTrue(contains('foo', 'foobar'));
        $this->assertTrue(contains('foo', 'barfoo'));
        $this->assertTrue(contains('foo', 'xfoox'));
        $this->assertFalse(contains('moo', 'barfoo'));
        $this->assertFalse(contains('', 'barfoo'));
    }
    public function testUpLo()
    {
        $this->assertEquals(strtoupper('foo'), upper('foo'));
        $this->assertEquals(strtolower('FOO'), lower('FOO'));
    }
    public function testReplace()
    {
        $this->assertEquals('foo', replace('bar', '', 'foobar'));
        $this->assertEquals('foo', replace('bar', '', 'fooBAR', ['insensitive' => true]));
    }
    public function testLen()
    {
        $this->assertEquals(3, length('foo'));
    }

    public function testPr()
    {
        ob_start();
        pr(['key' => 'value']);
        $out = ob_get_clean();
        $this->assertContains("Array\n(\n    [key] => value\n)", $out);
    }

    public function testdebug()
    {
        ob_start();
        debug(['key' => 'value']);
        $out = ob_get_clean();
        debug($out);
        $expected = <<< EOF
# # # # # DEBUG # # # # #
tests/TestCase/FunctionsTest.php Line: 140

Array
(
    [key] => value
)


# # # # # # # # # # # # #
EOF;
        $this->assertContains($expected, $out);
    }
}
