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

use Origin\Model\Marshaller;
use Origin\Model\Model;
use Origin\Model\Entity;
use Origin\Testsuite\TestTrait;

class MockMarkshaller extends Marshaller
{
    use TestTrait;
}

class MarshallerTest extends \PHPUnit\Framework\TestCase
{
    public function testBuildMap()
    {
        $Article = new Model(array('name' => 'Article', 'datasource' => 'test'));

        $Article->hasOne('Author');
        $Article->belongsTo('Category');
        $Article->hasMany('Comment');
        $Article->hasAndBelongsToMany('Tag');
        $Marshaller = new MockMarkshaller($Article);

        $result = $Marshaller->callMethod('buildAssociationMap');

        $expected = [
      'author' => 'Author',
      'category' => 'Category',
      'comments' => 'Comment',
      'tags' => 'Tag',
    ];
        $this->assertEquals($expected, $result);
    }

    public function testNewEntity()
    {
        $data = array(
      'id' => 1024,
      'title' => 'Some article title',
      'author' => array(
        'id' => 2048,
        'name' => 'Jon',
      ),
      'tags' => array(
        array('tag' => 'new'),
        array('tag' => 'featured'),
      ),
    );
        $Article = new Model(array('name' => 'Article', 'datasource' => 'test'));
        $Article->Tag = new Model(array('name' => 'Tag', 'datasource' => 'test'));

        $Marshaller = new Marshaller($Article);

        $entity = $Marshaller->newEntity($data, ['name' => 'Article']);

        $this->assertEquals(1024, $entity->id);
        $this->assertEquals('Some article title', $entity->title);
        $this->assertTrue(is_array($entity->author));
        $this->assertTrue(is_array($entity->tags));

        $Article->belongsTo('Author');
        $Article->hasMany('Tag');

        $entity = $Marshaller->newEntity($data, ['name' => 'Article']);

        $this->assertInstanceOf(Entity::class, $entity->author);
        $this->assertInstanceOf(Entity::class, $entity->tags[0]);
    }

    /**
     * @depends testNewEntity
     */
    public function testPatchEntity()
    {
        $data = array(
      'id' => 1024,
      'title' => 'Some article name',
      'author' => array(
        'id' => 2048,
        'name' => 'Jon',
      ),
      'tags' => array(
        array('tag' => 'new'),
        array('tag' => 'featured'),
      ),
    );

        $Article = new Model(array('name' => 'Article', 'datasource' => 'test'));
        $Article->Author = new Model(array('name' => 'Author', 'datasource' => 'test'));
        $Article->Tag = new Model(array('name' => 'Tag', 'datasource' => 'test'));

        $Article->hasOne('Author');
        $Article->hasMany('Tag');
        $Marshaller = new Marshaller($Article);
        $Entity = $Marshaller->newEntity($data, ['name' => 'Article']);

        $requestData = array(
        'title' => 'New Article Name',
        'unkown' => 'insert data',
        'author' => array(
          'name' => 'Claire',
        ),
        'tags' => array(
          array('tag' => 'published'),
          array('tag' => 'top ten'),
        ),
      );
        $patchedEntity = $Marshaller->patchEntity($Entity, $requestData);
        $this->assertEquals('New Article Name', $patchedEntity->title);
        $this->assertEquals('Claire', $patchedEntity->author->name);
        $this->assertEquals('published', $patchedEntity->tags[0]->tag);
        $this->assertEquals('top ten', $patchedEntity->tags[1]->tag);
    }
}
