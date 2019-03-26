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
use Origin\Model\ModelRegistry;

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

        $options = ['Author','Category','Comment','Tag'];
        $result = $Marshaller->callMethod('buildAssociationMap', [$options]);

        $expected = [
        'author' => 'one',
        'category' => 'one',
        'comments' => 'many',
        'tags' => 'many',
      ];
        $this->assertEquals($expected, $result);
    }


    public function testnew()
    {
        $data = array(
          'id' => 1024,
          'title' => 'Some article title',
          'description' => null,
          'author' => array(
            'id' => 2048,
            'name' => 'Jon',
            'created' => '2018-10-01 13:41:00'
          ),
          'tags' => array(
            array('tag' => 'new', 'created' => '2018-10-01 13:42:00'),
            array('tag' => 'featured'),
          ),
          'created' => '2018-10-01 13:43:00'
        );
        $Article = new Model(array('name' => 'Article', 'datasource' => 'test'));
        $Article->Tag = new Model(array('name' => 'Tag', 'datasource' => 'test'));
        $Article->Author = new Model(array('name' => 'User','alias'=>'Author', 'datasource' => 'test'));
        $Marshaller = new Marshaller($Article);

        $entity = $Marshaller->one($data, ['name' => 'Article']);

        $this->assertEquals(1024, $entity->id);
        $this->assertEquals('Some article title', $entity->title);
        $this->assertTrue(is_array($entity->author));
        $this->assertTrue(is_array($entity->tags));
        $this->assertEquals('2018-10-01 13:43:00', $entity->created);

        // Mass Assignment prevention
        $entity = $Marshaller->one($data, ['name' => 'Article','fields'=>['id','title']]);
        $this->assertTrue($entity->has('id'));
        $this->assertTrue($entity->has('title'));
        $this->assertFalse($entity->has('description'));

        $Article->belongsTo('Author');
        $Article->hasMany('Tag');
        $Marshaller = new Marshaller($Article);

        $entity = $Marshaller->one($data, ['name' => 'Article', 'associated'=>['Author','Tag']]);
      
        $this->assertEquals('2018-10-01 13:41:00', $entity->author->created);
        $this->assertEquals('2018-10-01 13:42:00', $entity->tags[0]->created);
        $this->assertInstanceOf(Entity::class, $entity->author);
        $this->assertInstanceOf(Entity::class, $entity->tags[0]);

        $entity = $Marshaller->one($data, [
          'name' => 'Article', 'associated'=>[
            'Author'=>['fields'=>['id','name']]
            ]]);
 
        $this->assertTrue($entity->author->has('name'));
        $this->assertFalse($entity->author->has('created'));
    }




    /**
     * @d epends testnew
     */
    public function testpatch()
    {
        $data = array(
      'id' => 1024,
      'title' => 'Some article name',
      'author' => array(
        'id' => 2048,
        'name' => 'Jon',
        'created' => ['date'=>'22/01/2019','time'=>'01:41pm']
      ),
      'tags' => array(
        array('tag' => 'new'),
        array('tag' => 'featured'),
      ),
      'created' => ['date'=>'22/01/2019','time'=>'10:20am']
    );

        $Article = new Model(array('name' => 'Article', 'datasource' => 'test'));
        $Article->Author = new Model(array('name' => 'Author', 'datasource' => 'test'));
        $Article->Tag = new Model(array('name' => 'Tag', 'datasource' => 'test'));
        
        $Article->hasOne('Author');
        $Article->hasMany('Tag');
        $Marshaller = new Marshaller($Article);
     
        $Entity = $Marshaller->one($data, ['name' => 'Article']);

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
     
        $patchedEntity = $Marshaller->patch($Entity, $requestData, ['associated'=>['Author','Tag']]);

        $this->assertEquals('New Article Name', $patchedEntity->title);
        $this->assertEquals('Claire', $patchedEntity->author->name);
        $this->assertEquals('published', $patchedEntity->tags[0]->tag);
        $this->assertEquals('top ten', $patchedEntity->tags[1]->tag);

        $Entity = $Marshaller->one(['id'=>1234], ['name' => 'Article']);
        $requestData['author']['location'] = 'New York';
         
        $patchedEntity = $Marshaller->patch($Entity, $requestData, ['fields'=>['id'],'associated'=>['Author'=>['fields'=>['id','name']]]]);
    
        $this->assertTrue($patchedEntity->author->has('name'));
        $this->assertFalse($patchedEntity->author->has('location'));
    }
}
