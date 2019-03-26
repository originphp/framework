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

namespace Origin\Test\Model;

use Origin\Model\Collection;
use Origin\Model\Entity;

class CollectionTest extends \PHPUnit\Framework\TestCase
{
    public function testArray()
    {
        $array = [
            new Entity(['title'=>'foo'], ['name'=>'Bookmark']),
            new Entity(['title'=>'bar'], ['name'=>'Bookmark']),
        ];
        $collection = new Collection($array);
        foreach ($collection as $key => $value) {
            $this->assertInstanceOf(Entity::class, $value);
        }
        $this->assertEquals(2, count($collection));
        $this->assertInstanceOf(Entity::class, $collection[0]);
        $this->assertInstanceOf(Entity::class, $collection[1]);
        $collection['key'] = 'value';
        $this->assertEquals('value', $collection['key']);
        $this->assertTrue(isset($collection['key']));
        unset($collection['key']);
        $collection[] = ['offsetget'];
    }
    public function testDebugInfo()
    {
        $array = [
            new Entity(['title'=>'foo'], ['name'=>'Bookmark'])
        ];
        $collection = new Collection($array);
        $data = print_r($collection, true);
        $this->assertContains('[0] => Origin\Model\Entity Object', $data);
    }
    public function testToJson()
    {
        $array = [
            new Entity(['title'=>'foo'], ['name'=>'Bookmark'])
        ];
        $collection = new Collection($array);

        $this->assertEquals('[{"title":"foo"}]', $collection->toJson());
    }
    public function testToXml()
    {
        $array = [
            new Entity(['title'=>'foo'], ['name'=>'Bookmark'])
        ];
        $collection = new Collection($array);

        $this->assertEquals("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<records><record><title>foo</title></record></records>\n", $collection->toXml());
    }
}
