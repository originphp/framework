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

    public function testEnv()
    {
        $this->assertNull(env('foo'));
        $this->assertEquals('bar', env('foo', 'bar'));
        $_SERVER['key1'] = 'foo';
        $_ENV['key2'] = 'bar';
        $this->assertEquals('foo', env('key1'));
        $this->assertEquals('bar', env('key2'));
    }

    public function testPr()
    {
        ob_start();
        pr(['key' => 'value']);
        $out = ob_get_clean();
        $this->assertStringContainsString("Array\n(\n    [key] => value\n)", $out);
    }

    public function testdebug()
    {
        ob_start();
        debug(['key' => 'value']);
        $out = ob_get_clean();
        
        $expected = <<< EOF
# # # # # DEBUG # # # # #
tests/TestCase/FunctionsTest.php Line: 84

Array
(
    [key] => value
)


# # # # # # # # # # # # #
EOF;
        $this->assertStringContainsString($expected, $out);
    }

    public function testDebugHtml()
    {
        ob_start();
        debug('<p>foo</p>', true);
        $out = ob_get_clean();
        
        $expected = <<< EOF
# # # # # DEBUG # # # # #
tests/TestCase/FunctionsTest.php Line: 105

&lt;p&gt;foo&lt;/p&gt;

# # # # # # # # # # # # #
EOF;
        $this->assertStringContainsString($expected, $out);
    }

    public function testUid()
    {
        $this->assertEquals(12, strlen(uid()));
    }

    public function testPj()
    {
        ob_start();
        pj(['key' => 'value']);
        $out = ob_get_clean();
        $this->assertStringContainsString("{\n    \"key\": \"value\"\n}\n", $out);
    }
}
