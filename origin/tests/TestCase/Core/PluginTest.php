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
        MockPlugin::load('Generate');
        $this->assertEquals(['Generate'], MockPlugin::loaded());
        $this->assertTrue(MockPlugin::loaded('Generate'));
        $config = MockPlugin::getLoaded();
        $this->assertEquals('/var/www/origin/tests/TestApp/plugins/make', $config['Generate']['path']);
        $this->assertTrue($config['Generate']['routes']);
        $this->assertTrue($config['Generate']['bootstrap']);
        
        // Test with no routes and bootstrap
        MockPlugin::load('Generate', ['routes'=>false,'bootstrap'=>false]);
        $this->assertTrue(MockPlugin::loaded('Generate'));
        $config = MockPlugin::getLoaded();
        $this->assertFalse($config['Generate']['routes']);
        $this->assertFalse($config['Generate']['bootstrap']);
    }

    public function testUnload()
    {
        MockPlugin::load('Generate');
        $this->assertTrue(MockPlugin::unload('Generate'));
        $this->assertFalse(MockPlugin::unload('Generate'));
        $this->assertFalse(MockPlugin::loaded('Generate'));
    }

    public function testRoutes()
    {
        MockPlugin::load('Generate');
        $this->assertTrue(MockPlugin::routes('Generate'));
        MockPlugin::loadRoutes();

        // Give include test
        Plugin::load('Generate', ['routes'=>false,'bootstrap'=>false]);
        $this->assertFalse(Plugin::routes('Generate')); // Test Include
        Plugin::load('Generate', ['routes'=>true]);
        $this->assertTrue(Plugin::routes('Generate'));
    }
    public function testInclude()
    {
        Plugin::load('Generate', ['routes'=>false]);
        $this->assertFalse(Plugin::routes('Generate'));
        Plugin::load('Generate', ['routes'=>true]);
        $this->assertTrue(Plugin::routes('Generate'));
        
        Plugin::load('Widget', ['bootstrap'=>true]);
        $this->assertFalse(Plugin::routes('Widget'));
    }
}
