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

use Origin\Model\Entity;

class EntityTest extends \PHPUnit\Framework\TestCase
{
    public function testSet()
    {
        $entity = new Entity();
        $entity->id = 1000;
        $this->assertTrue(isset($entity->id));
    }

    /**
     * @depends testSet
     */
    public function testGet()
    {
        $entity = new Entity();
        $entity->id = 1001;
        $entity->empty = null;

        $this->assertEquals(1001, $entity->id);
        $this->assertEquals(null, $entity->empty);
    }

    /**
     * @depends testSet
     */
    public function testIsset()
    {
        $entity = new Entity();
        $entity->id = 1002;
        $this->assertTrue(isset($entity->id));
        $this->assertFalse(isset($entity->name));
    }

    /**
     * @depends testSet
     */
    public function testUnset()
    {
        $entity = new Entity();
        $entity->id = 1003;
        unset($entity->id);
        $this->assertTrue(!isset($entity->id));
    }

    /**
     * @depends testSet
     * Empty runs isset first, then get
     */
    public function testEmptyFunc()
    {
        $entity = new Entity();
        $entity->id = null;
        $this->assertTrue(empty($entity->id));
        $this->assertTrue(empty($entity->name));
    }

    public function testCreate()
    {
        $data = array(
            'id' => 1004,
            'name' => 'EntityName',
        );
        $entity = new Entity($data);
        $this->assertEquals(1004, $entity->id);
        $this->assertEquals('EntityName', $entity->name);
    }

    /**
     * @depends testCreate
     */
    public function testToArray()
    {
        $article = new Entity();
        $article->id = 256;
        $author = new Entity();
        $author->name = 'Tony';
        $comment = new Entity();
        $comment->description = 'a comment';

        $article->author = $author;
        $article->comments = array(
      $comment,
    );
        $expected = array(
      'id' => 256,
      'author' => array('name' => 'Tony'),
      'comments' => array(
        array('description' => 'a comment'),
      ),
    );

        $this->assertEquals($expected, $article->toArray());
    }

    public function testHasProperty()
    {
        $data = array(
        'title' => 'Article Title',
        'author_id' => null,
    );

        $entity = new Entity($data);

        $this->assertTrue($entity->hasProperty('title'));
        $this->assertFalse($entity->hasProperty('author_id'));
        $this->assertFalse($entity->hasProperty('undefined'));
    }

    public function testInvalidate()
    {
        $data = array(
        'title' => 'Article Title',
        'author_id' => null,
    );

        $entity = new Entity($data);
        $entity->errors('title', 'invalid title');
     
        $this->assertEquals(['invalid title'], $entity->errors('title'));
    }

    /**
    * @depends testSet
    */
    public function testExtract()
    {
        $entity = new Entity();
        $entity->id = 1024;
        $entity->name = 'Foo';
        
        $expected = ['id'=>1024,'name'=>'Foo'];

        $this->assertEquals($expected, $entity->extract(['id','name','nonExistant']));
    }
}
