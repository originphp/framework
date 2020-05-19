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

use Origin\Core\Resolver;

class MockResolver extends Resolver
{
    public static $classes = [];

    public static function classExists(string $class): bool
    {
        return in_array($class, static::$classes);
    }

    public static function addClass($class)
    {
        static::$classes[] = $class;
    }
}

class ResolverTest extends \PHPUnit\Framework\TestCase
{
    public function testNameOnly()
    {
        $this->assertNull(MockResolver::className('NameOnly'));
    }

    public function testClassName()
    {
        $expected = 'App\Apple';
        MockResolver::addClass($expected);
        $this->assertEquals($expected, MockResolver::className('Apple'));

        $expected = 'App\Controller\Orange';
        MockResolver::addClass($expected);
        $this->assertEquals($expected, MockResolver::className('Orange', 'Controller'));

        $expected = 'App\Controller\Component\Strawberry';
        MockResolver::addClass($expected);
        $this->assertEquals($expected, MockResolver::className('Strawberry', 'Controller/Component'));

        $expected = 'App\Controller\Component\ApricotComponent';
        MockResolver::addClass($expected);
        $this->assertEquals($expected, MockResolver::className('Apricot', 'Controller/Component', 'Component'));

        $expected = 'App\Http\Controller\FigsController';
        MockResolver::addClass($expected);
        $this->assertEquals($expected, MockResolver::className('Figs', 'Controller', 'Controller', 'Http'));

        $expected = 'Myplugin\Controller\Component\BananaComponent';
        MockResolver::addClass($expected);
        $this->assertEquals($expected, MockResolver::className('Myplugin.BananaComponent', 'Controller/Component', null));

        $expected = 'Myplugin\Http\Controller\Component\BananaComponent';
        MockResolver::addClass($expected);
        $this->assertEquals($expected, MockResolver::className('Myplugin.Banana', 'Controller/Component', 'Component', 'Http'));
    }
}
