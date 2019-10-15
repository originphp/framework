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

use Origin\Configurable\InstanceConfigurable as Configurable;
use Origin\Http\View\Templater;
use Origin\Http\View\TemplateTrait;

class MockObject
{
    use TemplateTrait;
    use Configurable; // TemplateTrait uses this

    protected $defaultConfig = [
        'templates' => [],
    ];
}

class TemplateTraitTest extends \PHPUnit\Framework\TestCase
{
    public function testTemplates()
    {
        $object = new MockObject();
        $object->templates(['input' => '<input class="form-control">']);
        $this->assertEquals('<input class="form-control">', $object->templates('input'));
    }

    public function testTemplater()
    {
        $object = new MockObject();
        $object->config('templates', 'templates-test');
 
        $this->assertInstanceOf(Templater::class, $object->templater());
        $this->assertEquals('<p>{text}</p>', $object->templates('text'));
    }
}
