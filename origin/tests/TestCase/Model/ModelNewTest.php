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
/**
 * When many of the model tests were created or worked many features were not implemented or have
 * changed. This is a new set of tests to eventually replace the other model tests hoping it will be less code
 * and can benefit from other features. Also since this now uses fixtures, each setUp data is reset. This will be
 * make it easier to track down errors.
 */
namespace Origin\Test\ModelRefactored;

use Origin\TestSuite\OriginTestCase;
use Origin\Model\Model;
use Origin\Model\ModelRegistry;
use Origin\Model\Entity;
use Origin\Model\Collection;
use Origin\Exception\NotFoundException;

class Article extends Model
{
    public $datasource = 'test';
}


class ModelNewTest extends OriginTestCase
{
    public $fixtures = [
        'Framework.Article',
        'Framework.ArticlesTag',
        'Framework.Author',
        'Framework.Book',
        'Framework.Comment',
        'Framework.Tag',
        'Framework.Address',
    ];

    public function setUp()
    {
        $this->Article = new Model([
            'name'=>'Article',
            'datasource'=>'test'
            ]);
        $this->Author = new Model([
            'name'=>'Author',
            'datasource'=>'test'
            ]);
        $this->Book = new Model([
            'name'=>'Book',
            'datasource'=>'test'
            ]);
        $this->Comment = new Model([
            'name'=>'Comment',
            'datasource'=>'test'
            ]);

        $this->Tag = new Model([
            'name'=>'Tag',
            'datasource'=>'test'
            ]);

        $this->Address = new Model([
                'name'=>'Address',
                'datasource'=>'test'
                ]);
    
        
        ModelRegistry::set('Article', $this->Article);
        ModelRegistry::set('Author', $this->Author);
        ModelRegistry::set('Book', $this->Book);
        ModelRegistry::set('Comment', $this->Comment);
        ModelRegistry::set('Tag', $this->Tag);
        ModelRegistry::set('Address', $this->Address);
       
        parent::setUp();
    }

    public function _testFindFirst()
    {
        $result =  $this->Article->find('first');
        $this->assertInstanceOf(Entity::class, $result);
        
        $result = $this->Article->find('first', ['conditions'=>['id'=>'does-not-exist']]);
        $this->assertNull($result);
    }

    public function _testFindAll()
    {
        $result =  $this->Article->find('all');
        $this->assertInstanceOf(Collection::class, $result);
        
        $result = $this->Article->find('all', ['conditions'=>['id'=>'does-not-exist']]);
        $this->assertTrue(is_array($result));
    }
    public function _testFindConditions()
    {
        $result = $this->Article->find('first', ['conditions'=>['id'=>1001]]);
        $this->assertEquals(1001, $result->id);

        $result = $this->Article->find('first', ['conditions'=>['Article.id'=>1001]]);
        $this->assertEquals(1001, $result->id);
    }

    public function _testFindOrder()
    {
        $result = $this->Article->find('first', ['order'=>'created DESC']);
        $this->assertEquals(1002, $result->id);

        $result = $this->Article->find('first', ['order'=>['created ASC']]);
        $this->assertEquals(1000, $result->id);
    }

    public function _testFindFields()
    {
        $result = $this->Article->find('first', ['fields'=>['id','title']])->toArray();
        $this->assertEquals(['id','title'], array_keys($result));

        $options = ['fields'=>['DISTINCT (author_id)']];
        $result = $this->Article->find('all', $options);
        $this->assertEquals(2, count($result));

        $options = ['fields'=>['DISTINCT (author_id),title']];
        $result = $this->Article->find('all', $options);
        $this->assertEquals(3, count($result));

        $result = $this->Article->find('all', [
            'fields'=>['COUNT(*) as total','author_id'],
            'group'=>'author_id'
            ]);
        $this->assertEquals(1000, $result[0]->author_id);
        $this->assertEquals(2, $result[0]->total);
    }

    public function _testFindLimit()
    {
        $result = $this->Article->find('all', ['limit'=>2]);
        $this->assertEquals(2, count($result));
    }

    public function _testFindJoin()
    {
        $conditions = [
            'fields' => ['Article.id','Article.title','Author.name'],
            'joins'=>[]
        ];
        $conditions['joins'][] = [
            'table' => 'authors',
            'alias' => 'Author',
            'type' => 'LEFT' , // this is defualt,
            'conditions' => [
              'Author.id = Article.author_id'
            ]
           ];
       
        $result = $this->Article->find('first', $conditions);
        $this->assertEquals('Author #1', $result->author->name);
    }

    public function _testFindCount()
    {
        $result = $this->Article->find('count', ['fields'=>['really_does_not_matter']]);
        $this->assertEquals(3, $result);

        $result = $this->Article->find('count', ['conditions'=>['id'=>'does-not-exist']]);
        $this->assertEquals(0, $result);
    }

    public function _testFindList()
    {
        $list = $this->Article->find('list', ['fields'=>['id']]); // ['a','b','c']
        $this->assertEquals([1000,1001,1002], $list);

        $list = $this->Article->find('list', ['fields'=>['id','title']]); // ['a'=>'b']
        $this->assertEquals([1000=>'Article #1',1001=>'Article #2',1002=>'Article #3'], $list);

        $list = $this->Article->find('list', ['fields'=>['id','title','author_id']]); // ['c'=>['a'=>'b']
        $expected = [
            1001 => [1000=>'Article #1'],
            1000 => [1001=>'Article #2',1002=>'Article #3'],
        ];
        $this->assertEquals($expected, $list);
    }

    public function testFindCallbacks()
    {
        //Article::class
        $stub = $this->getMockForModel('Article', [
            'beforeFind','afterFind'
        ], ['className'=>Article::class]);
        
        $stub->expects($this->once())
        ->method('beforeFind')
        ->willReturn(false);

        $stub->expects($this->never())
        ->method('afterFind');

        $stub->find('first');

        $stub = $this->getMockForModel('Article', [
            'beforeFind','afterFind'
        ], ['className'=>Article::class]);
        
        $stub->expects($this->once())
        ->method('beforeFind')
        ->willReturn($this->returnArgument(0));

        $stub->expects($this->once())
        ->method('afterFind')
        ->willReturn($this->returnArgument(0));

        $stub->find('first');
    }

    public function _testFindAssociated()
    {
        $this->Article->belongsTo('Author');
        $this->Article->Author->hasOne('Address');
        $this->Article->Author->Address->belongsTo('Author');

        $this->Article->hasMany('Comment');
        $this->Article->Comment->belongsTo('Article');

        $this->Article->hasAndBelongsToMany('Tag');
        $this->Article->Tag->hasAndBelongsToMany('Tag');

        $result = $this->Article->find('first');
        $this->assertEquals(1000, $result->id);
        $this->assertNull($result->author);

        $result = $this->Article->find('first', [
            'associated'=>['Author']
            ]);
        $this->assertEquals(1000, $result->author_id);
        $this->assertEquals(1000, $result->author->id);
        $this->assertEquals('Author #1', $result->author->name);

        $this->assertTrue($result->author->has('created'));
        $result = $this->Article->find('first', [
            'associated'=>['Author'=>['fields'=>['id','name']]]
            ]);
      
        $this->assertFalse($result->author->has('created'));

        $result = $this->Article->find('first', [
            'associated'=>['Author'=>['associated'=>['Address']]]
            ]);
        $this->assertEquals(1000, $result->author_id);
        $this->assertEquals(1000, $result->author->id);
        $this->assertEquals(1000, $result->author->address->author_id);

        $this->assertTrue($result->author->address->has('created'));
        $result = $this->Article->find('first', [
            'associated'=>['Author'=>['associated'=>['Address'=>['fields'=>['id','description']]]]]
            ]);
        $this->assertFalse($result->author->address->has('created'));

        $result = $this->Article->Author->find('first', [
            'associated'=>['Address'=>['associated'=>'Author']],'conditions'=>['id'=>1000]
            ]);
      
        $this->assertEquals(1000, $result->id);
        $this->assertEquals(1000, $result->address->author_id);
        $this->assertEquals(1000, $result->address->author->id);
    }

    public function _testExists()
    {
        $this->assertTrue($this->Article->exists(1000));
        $this->assertFalse($this->Article->exists(10000000));
    }

    public function _testGet()
    {
        $result = $this->Article->get(1001);
        $this->assertEquals('Article #2', $result->title);
        $this->expectException(NotFoundException::class);
        $this->Article->get(10000000);
    }

    public function _testQuery()
    {
        $result = $this->Article->query('SELECT title from articles');
        $this->assertEquals('Article #1', $result[0]['title']);
        $result = $this->Article->query('SELECT title from articles WHERE id = :id', ['id'=>1002]);
        $this->assertEquals('Article #3', $result[0]['title']);

        $result = $this->Article->query('DELETE from articles WHERE id = :id', ['id'=>1000]);
        $this->assertTrue($result);
    }

    public function _testCrud()
    {
        # # # CREATE # # #
        $article = $this->Article->new();
        $article->author_id = 1001;
        $article->title = 'Testing CRUD';
        $article->description = 'Just going to test it all';
       
        $this->assertNotEmpty($article->modified());
        $this->assertTrue($this->Article->save($article));
        $this->assertNotEmpty($article->id);
        $this->assertNotEmpty($this->Article->id);
        $this->assertEmpty($article->modified());

        # # # READ # # #
        $result = $this->Article->get($article->id);
        $this->assertEquals('Testing CRUD', $article->title);

        # # # UPDATE # # #
        $requestData = ['title'=>'Testing Update in CRUD','description'=>'Lovely Jubely'];
        $article = $this->Article->patch($result, $requestData);
        $this->assertNotEmpty($article->modified());
        $this->assertTrue($this->Article->save($article));
        $this->assertEmpty($article->modified());

        $result = $this->Article->get($article->id);
        $this->assertEquals('Testing Update in CRUD', $article->title);

        # # # DELETE # # #
        $this->assertTrue($this->Article->delete($article));
        $this->assertFalse($this->Article->delete($article));
    }

    public function _testSaveValidation()
    {
        $article = $this->Article->new();
        $this->assertFalse($this->Article->save($article));

        $this->Article->validate('title', [
            'rule'=>'notBlank',
            'required'=>true,
            'on'=>'create']);
        
        $article->author_id = 1001;
        $article->title = null;
        $article->description = 'Title is blank so it should fail';
        $this->assertFalse($this->Article->save($article));
        $this->assertNotEmpty($article->errors());

        $article = $this->Article->new();
        $article->author_id = 1001;
        $article->title = 'Now this should work';
        $article->description = 'did not want to call reset';
   
        $this->assertTrue($this->Article->save($article));
    }
}
