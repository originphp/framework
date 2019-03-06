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

use Origin\Core\Plugin;
use Origin\Core\Exception\MissingPluginException;

class MockPlugin extends Plugin
{
    public static function getLoaded()
    {
        return static::$loaded;
    }
    public static $fileFound = true;
 
    public static function include(string $filename)
    {
        return static::$fileFound;
    }
}
class PluginTest extends \PHPUnit\Framework\TestCase
{
    public function testLoadException()
    {
        $this->expectException(MissingPluginException::class);
        Plugin::load('PluginThatDoesNotExist');
    }

    public function testLoad()
    {
        // test with routes and bootstrap
        MockPlugin::load('Make');
        $this->assertEquals(['Make'], MockPlugin::loaded());
        $this->assertTrue(MockPlugin::loaded('Make'));
        $config = MockPlugin::getLoaded();
        $this->assertEquals('/var/www/origin/tests/TestApp/plugins/make/src', $config['Make']['path']);
        $this->assertTrue($config['Make']['routes']);
        $this->assertTrue($config['Make']['bootstrap']);
        
        // Test with no routes and bootstrap
        MockPlugin::load('Make', ['routes'=>false,'bootstrap'=>false]);
        $this->assertTrue(MockPlugin::loaded('Make'));
        $config = MockPlugin::getLoaded();
        $this->assertFalse($config['Make']['routes']);
        $this->assertFalse($config['Make']['bootstrap']);
    }

    public function testUnload()
    {
        MockPlugin::load('Make');
        $this->assertTrue(MockPlugin::unload('Make'));
        $this->assertFalse(MockPlugin::unload('Make'));
        $this->assertFalse(MockPlugin::loaded('Make'));
    }

    public function testRoutes()
    {
        MockPlugin::load('Make');
        $this->assertTrue(MockPlugin::routes('Make'));
        MockPlugin::loadRoutes();

        // Give include test
        Plugin::load('Make', ['routes'=>false,'bootstrap'=>false]);
        $this->assertFalse(Plugin::routes('Make')); // Test Include
    }
    public function testInclude()
    {
        Plugin::load('Make');
        $this->assertFalse(Plugin::routes('Make'));
    }
}
