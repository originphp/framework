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

namespace Origin\Test\View;

use Origin\View\Templater;
use Origin\Exception\Exception;

class TemplaterTest extends \PHPUnit\Framework\TestCase
{
    public function testGet()
    {
        $expected = '<a href="{url}">{text}</a>';
        $templater = new Templater(['link'=>$expected]);
        $this->assertEquals($expected, $templater->get('link'));
    }

    public function testSet()
    {
        $expected = '<a href="{url}">{text}</a>';
        $templater = new Templater();
        $templater->set(['link'=>$expected]);
        $this->assertEquals($expected, $templater->get('link'));
    }

    public function testFormat()
    {
        $expected = '<a href="{url}">{text}</a>';
        $templater = new Templater(['link'=>$expected]);

        $data = ['url' => '#', 'text' => 'my link'];
        $expected = '<a href="#">my link</a>';
 
        $this->assertEquals($expected, $templater->format('link', $data));
    }
}
