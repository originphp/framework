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

use Origin\Core\Dot;

class DotTest extends \PHPUnit\Framework\TestCase
{
    public function testSet()
    {
        $Dot = new Dot();
        $Dot->set('App.encoding', 'UTF-8');
        $expected = ['App' => ['encoding' => 'UTF-8']];
        $this->assertSame($expected, $Dot->items());

        $Dot = new Dot();
        $Dot->set('App', ['encoding' => 'UTF-8']);
        $this->assertSame($expected, $Dot->items());

        $Dot->set('System.settings.server', 'localhost');
        $items = $Dot->items();
        $this->assertArrayHasKey('server', $items['System']['settings']);

        $Dot = new Dot();
        $Dot->set('key', 'value');
        $expected = ['key' => 'value'];
        $this->assertSame($expected, $Dot->items());
    }

    /**
     * @depends testSet
     */
    public function testGet()
    {
        $Dot = new Dot();
        $Dot->set('App.baseDirectory', 'blog');
        $expected = 'blog';
        $this->assertSame($expected, $Dot->get('App.baseDirectory'));
        $this->assertNull($Dot->get('App.secretPassword'));

        $Dot->set('App.encoding', 'UTF-8');
        $expected = ['baseDirectory' => 'blog', 'encoding' => 'UTF-8'];
        $this->assertSame($expected, $Dot->get('App'));

        $Dot->set('System.settings.server', 'localhost');
        $this->assertSame('localhost', $Dot->get('System.settings.server'));

        $Dot = new Dot();
        $Dot->set('key', 'value');
        $this->assertEquals('value', $Dot->get('key'));
        $this->assertNull($Dot->get('nonExistant', null));
    }

    /**
     * @depends testSet
     */
    public function testHas()
    {
        $Dot = new Dot();
        $this->assertFalse($Dot->has('App.encoding'));

        $Dot->set('App.encoding', 'UTF-8');
        $this->assertTrue($Dot->has('App.encoding'));

        $Dot->set('System.settings.server', 'localhost');
        $this->assertTrue($Dot->has('System.settings.server'));
        $this->assertFalse($Dot->has('NonExistant'));
    }

    /**
     * @depends testSet
     */
    public function testDelete()
    {
        $Dot = new Dot();
        $Dot->set('App.baseDirectory', 'blog');
        $Dot->set('App.encoding', 'UTF-8');
        $Dot->set('App.turbo', true);

        $items = $Dot->items();
        $this->assertArrayHasKey('encoding', $items['App']);
        $this->assertTrue($Dot->delete('App.encoding'));
        $this->assertFalse($Dot->delete('App.encoding'));
       
        $items = $Dot->items();
        $this->assertArrayNotHasKey('encoding', $items['App']);

        $this->assertFalse($Dot->delete('FozzyWozzy'));
        $this->assertFalse($Dot->delete('App.nonExistant.deeper'));
    }

    public function testCycle()
    {
        $data = [];

        $Dot = new Dot($data);
        $Dot->set('foo', 'bar');
        $data = $Dot->items();
        $this->assertEquals('bar', $Dot->get('foo'));

        $Dot = new Dot($data);
        $this->assertEquals('bar', $Dot->get('foo'));
    }
}
