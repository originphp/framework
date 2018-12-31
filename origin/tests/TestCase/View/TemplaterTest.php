<?php
/**
 * OriginPHP Framework
 * Copyright 2018 Jamiel Sharief.
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
    public function testFormat()
    {
        $Templater = new Templater();

        $template = '<a href="{url}">{text}</a>';

        $data = ['url' => '#', 'text' => 'my link'];
        $expected = '<a href="#">my link</a>';
        $result = $Templater->format($template, $data);
        $this->assertEquals($expected, $result);

        $data = ['text' => 'my link'];
        $expected = '<a href="#">my link</a>';
        $this->expectException(Exception::class);
        $result = $Templater->format($template, $data);
    }
}
