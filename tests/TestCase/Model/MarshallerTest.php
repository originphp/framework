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

use Origin\Model\Model;
use Origin\Model\Entity;
use Origin\Model\Marshaller;
use Origin\Testsuite\TestTrait;

class MockMarkshaller extends Marshaller
{
    use TestTrait;
}

class MarshallerTest extends \PHPUnit\Framework\TestCase
{
    public function testBuildMap()
    {
        $Article = new Model(['name' => 'Article', 'connection' => 'test']);

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
        $data = [
            'id' => 1024,
            'title' => 'Some article title',
            'description' => null,
            'author' => [
                'id' => 2048,
                'name' => 'Jon',
                'created' => '2018-10-01 13:41:00',
            ],
            'tags' => [
                ['tag' => 'new', 'created' => '2018-10-01 13:42:00'],
                ['tag' => 'featured'],
            ],
            'created' => '2018-10-01 13:43:00',
        ];
        $Article = new Model(['name' => 'Article', 'connection' => 'test']);
        $Article->Tag = new Model(['name' => 'Tag', 'connection' => 'test']);
        $Article->Author = new Model(['name' => 'User','alias' => 'Author', 'connection' => 'test']);
        $Marshaller = new Marshaller($Article);

        $entity = $Marshaller->one($data, ['name' => 'Article']);

        $this->assertEquals(1024, $entity->id);
        $this->assertEquals('Some article title', $entity->title);
        $this->assertTrue(is_array($entity->author));
        $this->assertTrue(is_array($entity->tags));
        $this->assertEquals('2018-10-01 13:43:00', $entity->created);

        // Mass Assignment prevention
        $entity = $Marshaller->one($data, ['name' => 'Article','fields' => ['id','title']]);
        $this->assertTrue($entity->has('id'));
        $this->assertTrue($entity->has('title'));
        $this->assertFalse($entity->has('description'));

        $Article->belongsTo('Author');
        $Article->hasMany('Tag');
        $Marshaller = new Marshaller($Article);

        $entity = $Marshaller->one($data, ['name' => 'Article', 'associated' => ['Author','Tag']]);
      
        $this->assertEquals('2018-10-01 13:41:00', $entity->author->created);
        $this->assertEquals('2018-10-01 13:42:00', $entity->tags[0]->created);
        $this->assertInstanceOf(Entity::class, $entity->author);
        $this->assertInstanceOf(Entity::class, $entity->tags[0]);

        $entity = $Marshaller->one($data, [
            'name' => 'Article', 'associated' => [
                'Author' => ['fields' => ['id','name']],
            ], ]);
 
        $this->assertTrue($entity->author->has('name'));
        $this->assertFalse($entity->author->has('created'));

        $data = ['name' => 'Article','author' => 'bad data'];
        $entity = $Marshaller->one($data, ['associated' => ['Author']]);
        $this->assertNull($entity->author);
    }

    /**
     * @d epends testnew
     */
    public function testpatch()
    {
        $data = [
            'id' => 1024,
            'title' => 'Some article name',
            'author' => [
                'id' => 2048,
                'name' => 'Jon',
                'created' => ['date' => '22/01/2019','time' => '01:41pm'],
            ],
            'tags' => [
                ['tag' => 'new'],
                ['tag' => 'featured'],
            ],
            'created' => ['date' => '22/01/2019','time' => '10:20am'],
        ];

        $Article = new Model(['name' => 'Article', 'connection' => 'test']);
        $Article->Author = new Model(['name' => 'Author', 'connection' => 'test']);
        $Article->Tag = new Model(['name' => 'Tag', 'connection' => 'test']);
        
        $Article->hasOne('Author');
        $Article->hasMany('Tag');
        $Marshaller = new Marshaller($Article);
     
        $Entity = $Marshaller->one($data, ['name' => 'Article']);

        $requestData = [
            'title' => 'New Article Name',
            'unkown' => 'insert data',
            'author' => [
                'name' => 'Claire',
            ],
            'tags' => [
                ['tag' => 'published'],
                ['tag' => 'top ten'],
            ],
        ];
     
        $patchedEntity = $Marshaller->patch($Entity, $requestData, ['associated' => ['Author','Tag']]);

        $this->assertEquals('New Article Name', $patchedEntity->title);
        $this->assertEquals('Claire', $patchedEntity->author->name);
        $this->assertEquals('published', $patchedEntity->tags[0]->tag);
        $this->assertEquals('top ten', $patchedEntity->tags[1]->tag);

        $Entity = $Marshaller->one(['id' => 1234], ['name' => 'Article']);
        $requestData['author']['location'] = 'New York';
         
        $patchedEntity = $Marshaller->patch($Entity, $requestData, ['fields' => ['id','author'],'associated' => ['Author' => ['fields' => ['id','name']]]]);
    
        $this->assertTrue($patchedEntity->author->has('name'));
        $this->assertFalse($patchedEntity->author->has('location'));

        $data = ['name' => 'Article','author' => 'bad data'];
        $entity = $Marshaller->patch($Entity, $data, ['associated' => ['Author']]);
        $this->assertNull($entity->author);
    }
}
