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
use Origin\TestSuite\OriginTestCase;

class MockMarkshaller extends Marshaller
{
    use TestTrait;
}

class Article extends Model
{
}

class Author extends Model
{
}

class Comment extends Model
{
}

class MarshallerTest extends OriginTestCase
{
    public $fixtures = ['Origin.Article','Origin.Author','Origin.Tag','Origin.Comment'];

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
     * @depends testnew
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

    public function testPatchOne()
    {
        $data = [
            'id' => 1024,
            'title' => 'Some article name',
            'author' => [
                'id' => 2048,
                'name' => 'Jon',
                'created' => ['date' => '22/01/2019','time' => '01:41pm'],
            ]
        ];

        $Article = new Model(['name' => 'Article', 'connection' => 'test']);
        $Article->Author = new Model(['name' => 'Author', 'connection' => 'test']);
        $Article->Tag = new Model(['name' => 'Tag', 'connection' => 'test']);
        
        $Article->hasOne('Author');
        $Article->hasMany('Tag');
        $Marshaller = new Marshaller($Article);
     
        $entity = $Marshaller->one($data, ['name' => 'Article']);

        $requestData = [
            'title' => 'New Article Name',
            'unkown' => 'insert data',
            'author' => [
                'name' => 'Claire',
            ],
        ];
        $patched = $Marshaller->patch($entity, $requestData, ['associated' => ['Author']]);
        $this->assertInstanceOf(Entity::class, $patched->author);
        $this->assertEquals('Author', $patched->author->name());
    }

    public function testPatchOneExisting()
    {
        // Load models into registry as we are using custom class
        $Article = $this->loadModel('Article', ['className' => Article::class]);
        $this->loadModel('Author', ['className' => Author::class]);
        
        $Article->belongsTo('Author');

        $record = $Article->get(1000, ['associated'=>['Author']]);
        $data = [
            'id' => 1000,
            'author' => [
                'id' => 1001,
                'name' => 'Neo'
            ]
        ];

        $patched = $Article->patch($record, $data);
        $this->assertEquals('Neo', $patched->author->name);
        $this->assertNotNull($patched->author->description);

        # Test Patch wrong id
        $record = $Article->get(1000, ['associated' => ['Author']]);
 
        $data = [
            'id' => 1000,
            'author' => [
                'id' => 99,
                'name' => 'Fred'
            ]
        ];

        $patched = $Article->patch($record, $data);
        $this->assertEquals('Fred', $patched->author->name);
        $this->assertNull($patched->author->description);
    }

    public function testPatchMany()
    {
        $data = [
            'id' => 1000,
            'title' => 'Some article name',
            'comments' => [
                [
                    'id' => 123,
                    'article_id' => 1000,
                    'description' => 'foo',
                ],
                [
                    'id' => 456,
                    'article_id' => 1000,
                    'description' => 'bar',
                ]
            ]
        ];

        $Article = new Model(['name' => 'Article', 'connection' => 'test']);
        $Article->Comment = new Model(['name' => 'Comment', 'connection' => 'test']);
        $Article->hasMany('Comment');

        $entity = $Article->new($data, ['associated' => true]);
        $requestData = [
            'title' => 'New Article Name',
            'unkown' => 'insert data',
            'comments' => [
                [
                    'article_id' => 1000,
                    'description' => 'bar-foo',
                ],
                [
                    'id' => 456,
                    'article_id' => 1000,
                    'description' => 'foo-bar',
                ]
            ],
        ];
        $patched = $Article->patch($entity, $requestData);

        $this->assertNull($patched['comments'][0]->id);
        $this->assertEquals('bar-foo', $patched['comments'][0]->description);

        $this->assertEquals(456, $patched['comments'][1]->id);
        $this->assertEquals('foo-bar', $patched['comments'][1]->description);
    }

    public function testPatchManyExisting()
    {
        $Article = $this->loadModel('Article', ['className' => Article::class]);
        $this->loadModel('Comment', ['className' => Comment::class]);
        
        $Article->hasMany('Comment');

        $record = $Article->get(1000, ['associated' => ['Comment']]);
 
        $requestData = [
            'title' => 'Article #1',
            'comments' => [
                [
                    'id' => 1001,
                    'article_id' => 1000,
                    'description' => 'change comment',
                ],
                [
                    'id' => 99,
                    'article_id' => 1000,
                    'description' => 'unkown id',
                ],
            ],
        ];

        $patched = $Article->patch($record, $requestData);

        // Test data is patched
        $this->assertEquals(1001, $patched['comments'][0]->id);
        $this->assertEquals('change comment', $patched['comments'][0]->description);
        $this->assertEquals('2019-03-27 13:11:00', $patched['comments'][0]->created); # important to see if it was patched or replaced

        // Test data is overwritten as it cant be patched
        $this->assertEquals(99, $patched['comments'][1]->id);
        $this->assertEquals('unkown id', $patched['comments'][1]->description);
        $this->assertNull($patched['comments'][1]->created); # important to see if it was patched or replaced
    }

    public function testPatchHasOne()
    {
        $Article = $this->loadModel('Article', ['className' => Article::class]);
        $this->loadModel('Comment', ['className' => Comment::class]);
        
        $Article->hasOne('Comment');

        $record = $Article->get(1000, ['associated' => ['Comment']]);

        $requestData = [
            'title' => 'Article #1',
            'comment' => [
                'id' => 1001,
                'article_id' => 1002,
                'description' => 'change comment'
            ]
        ];

        $patched = $Article->patch($record, $requestData);

        $this->assertEquals('change comment', $patched['comment']->description);
        $this->assertEquals('2019-03-27 13:11:00', $patched['comment']->created); # important to see if it was patched or replaced

        # Test no match
        $record = $Article->get(1000, ['associated' => ['Comment']]);
        $requestData['comment']['id'] = 1234;
        $patched = $Article->patch($record, $requestData);
        $this->assertEquals('change comment', $patched['comment']->description);
        $this->assertNull($patched['comment']->created);
    }

    public function testPatchSameValueDifferentTypes()
    {
        // Load models into registry as we are using custom class
        $Article = $this->loadModel('Article', ['className' => Article::class]);
    
        $record = $Article->get(1000);
        $record->created = null;

        $data = [
            'id' => '1000', // string
            'created' => '', // null string
            'modified' => 'foo'
        ];
    
        $patched = $Article->patch($record, $data);
        $this->assertTrue($record->modified('modified')); // sanity check

        $this->assertFalse($record->modified('id'));
        $this->assertFalse($record->modified('created'));
    }
}
