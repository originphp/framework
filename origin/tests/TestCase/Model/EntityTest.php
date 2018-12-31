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

namespace Origin\Test\Model;

use Origin\Model\Entity;

class EntityTest extends \PHPUnit\Framework\TestCase
{
    public function testSet()
    {
        $Entity = new Entity();
        $Entity->id = 1000;
        $this->assertTrue(isset($Entity->id));
    }

    /**
     * @depends testSet
     */
    public function testGet()
    {
        $Entity = new Entity();
        $Entity->id = 1001;

        $this->assertEquals(1001, $Entity->id);
    }

    /**
     * @depends testSet
     */
    public function testIsset()
    {
        $Entity = new Entity();
        $Entity->id = 1002;
        $this->assertTrue(isset($Entity->id));
        $this->assertFalse(isset($Entity->name));
    }

    /**
     * @depends testSet
     */
    public function testUnset()
    {
        $Entity = new Entity();
        $Entity->id = 1003;
        unset($Entity->id);
        $this->assertTrue(!isset($Entity->id));
    }

    /**
     * @depends testSet
     * Empty runs isset first, then get
     */
    public function testEmptyFunc()
    {
        $Entity = new Entity();
        $Entity->id = null;
        $this->assertTrue(empty($Entity->id));
        $this->assertTrue(empty($Entity->name));
    }

    public function testCreate()
    {
        $data = array(
      'id' => 1004,
      'name' => 'EntityName',
    );
        $Entity = new Entity($data);
        $this->assertEquals(1004, $Entity->id);
        $this->assertEquals('EntityName', $Entity->name);
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

        $Entity = new Entity($data);

        $this->assertTrue($Entity->hasProperty('title'));
        $this->assertFalse($Entity->hasProperty('author_id'));
        $this->assertFalse($Entity->hasProperty('undefined'));
    }

    public function testInvalidate()
    {
        $data = array(
        'title' => 'Article Title',
        'author_id' => null,
    );

        $Entity = new Entity($data);
        $Entity->invalidate('title', 'invalid title');
        $this->assertEquals(['invalid title'], $Entity->getError('title'));
    }
}
