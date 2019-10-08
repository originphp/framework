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

namespace Origin\Test\Http\View;

use Origin\Exception\Exception;
use Origin\Http\View\Templater;

class TemplaterTest extends \PHPUnit\Framework\TestCase
{
    public function testGet()
    {
        $expected = '<a href="{url}">{text}</a>';
        $templater = new Templater(['link' => $expected]);
        $this->assertEquals($expected, $templater->get('link'));
        $this->assertEquals(['link' => $expected], $templater->get());
        $this->assertNull($templater->get('foo'));
    }

    public function testSet()
    {
        $expected = '<a href="{url}">{text}</a>';
        $templater = new Templater();
        $templater->set(['link' => $expected]);
        $this->assertEquals($expected, $templater->get('link'));
    }

    public function testFormat()
    {
        $expected = '<a href="{url}">{text}</a>';
        $templater = new Templater(['link' => $expected]);

        $data = ['url' => '#', 'text' => 'my link'];
        $expected = '<a href="#">my link</a>';
 
        $this->assertEquals($expected, $templater->format('link', $data));

        $this->expectException(Exception::class);
        $templater->format('foo', ['key' => 'value']);
    }

    public function testLoad()
    {
        $templater = new Templater();
        $this->assertTrue($templater->load('templates-test'));
        $this->assertEquals('<p>{text}</p>', $templater->get('text'));

        $templater = new Templater();
        $this->assertTrue($templater->load('Widget.templates-test'));
        $this->assertEquals('<p>{text}</p>', $templater->get('p'));

        $templater = new Templater();
        $this->expectException(Exception::class);
        $templater->load('super-templates');
    }
}
