<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
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
tests/TestCase/Core/FunctionsTest.php Line: 63

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
tests/TestCase/Core/FunctionsTest.php Line: 84

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

    public function testTmpPath()
    {
        $this->assertEquals(TMP, tmp_path());
        $this->assertEquals(TMP .'/storage/foo.json', tmp_path('storage/foo.json'));
    }
}
