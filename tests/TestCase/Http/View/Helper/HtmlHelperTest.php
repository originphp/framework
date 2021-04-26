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

namespace Origin\Test\Http\View\Helper;

use Origin\Core\Plugin;
use Origin\Http\Router;
use Origin\Http\Request;
use Origin\Http\Response;
use Origin\Http\View\View;
use Origin\Http\Controller\Controller;
use Origin\Http\View\Helper\HtmlHelper;
use Origin\Http\View\Exception\NotFoundException;

class HtmlHelperTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $controller = new Controller(new Request(), new Response());
        $this->Html = new HtmlHelper(new View($controller));
        Plugin::load('Widget');
    }

    public function testLink()
    {
        $request = new Request('/articles');
      
        Router::request($request);
   
        $expected = '<a href="/">view</a>';
        $result = $this->Html->link('view', null);
        $this->assertEquals($expected, $result);

        $expected = '<a href="/articles/view/1024">view</a>';
        $result = $this->Html->link('view', ['action' => 'view', 1024]);
        $this->assertEquals($expected, $result);

        $expected = '<a href="/articles/view/2048" class="custom">view</a>';
        $result = $this->Html->link('view', ['action' => 'view', 2048], ['class' => 'custom']);
        $this->assertEquals($expected, $result);
    }

    public function testCss()
    {
        $expected = '<link rel="stylesheet" type="text/css" href="https://example.com/something.css" />';
        $this->assertSame($expected, $this->Html->css('https://example.com/something.css'));

        $expected = '<link rel="stylesheet" type="text/css" href="/css/form.css" />';
        $this->assertSame($expected, $this->Html->css('form'));

        $expected = '<link rel="stylesheet" type="text/css" href="/assets/css/form.css" />';
        $this->assertSame($expected, $this->Html->css('/assets/css/form.css'));

        $expected = '<link rel="stylesheet" type="text/css" href="/assets/css/form.css?foo=bar" />';
        $this->assertSame($expected, $this->Html->css('/assets/css/form.css?foo=bar'));
    
        $expected = "<style>\n.plugin { color:#fff }\n</style>";
        $this->assertSame($expected, $this->Html->css('Widget.default.css'));

        $expected = "<style>\n@import \"external.css\";\n</style>";
        $this->assertSame($expected, $this->Html->css('foo', ['inline' => true]));

        $this->expectException(NotFoundException::class);
        $this->Html->css('Widget.does-not-exist.css');
    }

    public function testJs()
    {
        $expected = '<script type="text/javascript" src="https://example.com/something.js"></script>';
        $this->assertSame($expected, $this->Html->js('https://example.com/something.js'));

        $expected = '<script type="text/javascript" src="/js/form.js"></script>';
        $this->assertSame($expected, $this->Html->js('form'));

        $expected = '<script type="text/javascript" src="/assets/js/form.js"></script>';
        $this->assertSame($expected, $this->Html->js('/assets/js/form.js'));
    
        $this->assertStringContainsString(
            file_get_contents(ROOT . '/tests/TestApp/plugins/widget/public/js/default.js'),
            $this->Html->js('Widget.default.js')
        );

        $expected = "<script>\nvar foo = null;\n</script>";
        $this->assertSame($expected, $this->Html->js('foo', ['inline' => true]));

        $this->expectException(NotFoundException::class);
        $this->Html->js('Widget.does-not-exist.js');
    }

    public function testImg()
    {
        $expected = '<img src="logo.png/img/logo.png">';
        $this->assertSame($expected, $this->Html->img('logo.png'));
        $expected = '<img src="/assets/img/logo.png">';
        $this->assertSame($expected, $this->Html->img('/assets/img/logo.png'));
    }

    public function testDiv()
    {
        $content = '<p>Hello</p>';
        $expected = '<div class="important"><p>Hello</p></div>';
        $this->assertEquals($expected, $this->Html->div($content, ['class' => 'important']));
    }

    public function testTag()
    {
        $content = '<p>Hello</p>';
        $expected = '<div class="important"><p>Hello</p></div>';
        $this->assertEquals($expected, $this->Html->tag('div', $content, ['class' => 'important']));
    }
}
