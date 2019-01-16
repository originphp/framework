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

use Origin\Core\Resolver;

class MockResolver extends Resolver
{
    public static $classes = [];

    public static function classExists($class)
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
    public function testComponent()
    {
        MockResolver::addClass('Origin\Controller\Component\FlashComponent');
        $result = MockResolver::className('Flash', 'Controller/Component', 'Component');
        $expected = 'Origin\Controller\Component\FlashComponent';
        $this->assertEquals($expected, $result);

        $result = MockResolver::className('FlashComponent', 'Controller/Component');
        $this->assertEquals($expected, $result);

        MockResolver::addClass('App\Controller\Component\MathComponent');
        $result = MockResolver::className('Math', 'Controller/Component', 'Component');
        $expected = 'App\Controller\Component\MathComponent';
        $this->assertEquals($expected, $result);

        $result = MockResolver::className('MathComponent', 'Controller/Component');
        $this->assertEquals($expected, $result);
    }

    public function testHelper()
    {
        MockResolver::addClass('Origin\View\Helper\FormHelper');
        $result = MockResolver::className('Form', 'View/Helper', 'Helper');
        $expected = 'Origin\View\Helper\FormHelper';
        $this->assertEquals($expected, $result);

        $result = MockResolver::className('FormHelper', 'View/Helper');
        $this->assertEquals($expected, $result);

        MockResolver::addClass('App\View\Helper\ListHelper');
        $result = MockResolver::className('List', 'View/Helper', 'Helper');
        $expected = 'App\View\Helper\ListHelper';
        $this->assertEquals($expected, $result);

        $result = MockResolver::className('ListHelper', 'View/Helper');
        $this->assertEquals($expected, $result);
    }

    public function testBehavior()
    {
        MockResolver::addClass('Origin\Model\Behavior\TimestampBehavior');
        $result = MockResolver::className('Timestamp', 'Model/Behavior', 'Behavior');
        $expected = 'Origin\Model\Behavior\TimestampBehavior';
        $this->assertEquals($expected, $result);

        $result = MockResolver::className('TimestampBehavior', 'Model/Behavior');
        $this->assertEquals($expected, $result);

        MockResolver::addClass('App\Model\Behavior\TreeBehavior');
        $result = MockResolver::className('Tree', 'Model/Behavior', 'Behavior');
        $expected = 'App\Model\Behavior\TreeBehavior';
        $this->assertEquals($expected, $result);

        $result = MockResolver::className('TreeBehavior', 'Model/Behavior');
        $this->assertEquals($expected, $result);
    }
}
