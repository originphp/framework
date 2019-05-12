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
use Origin\Model\Behavior\Behavior;
use Origin\Exception\Exception;
use Origin\Exception\InvalidArgumentException;
use Origin\Model\Exception\MissingModelException;

/**
 * Used By Mocks
 */
class Article extends Model
{
    public $datasource = 'test';
}

class ModelTest extends OriginTestCase
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

    /**
     * Model
     *
     * @var \Origin\Model\Model;
     */
    public $Article = null;

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


    public function testConstruct()
    {
        $Model = new Model();
        $this->assertEquals('Model', $Model->name);
        $this->assertEquals('Model', $Model->alias);
        $this->assertEquals('models', $Model->table);
        $this->assertEquals('default', $Model->datasource);

        $Post = new Model(array('name' => 'Post'));
        $this->assertEquals('Post', $Post->name);
        $this->assertEquals('Post', $Post->alias);
        $this->assertEquals('posts', $Post->table);
        $this->assertEquals('default', $Model->datasource);

        $Post = new Model(array('name' => 'Post', 'alias' => 'BlogPost', 'datasource' => 'test'));
        $this->assertEquals('Post', $Post->name);
        $this->assertEquals('BlogPost', $Post->alias);
        $this->assertEquals('posts', $Post->table);
        $this->assertEquals('test', $Post->datasource);
    }

    public function testFields()
    {
        $fields = $this->Article->fields();

        $expected = [
          'Article.id',
          'Article.author_id',
          'Article.title',
          'Article.body',
          'Article.created',
          'Article.modified',
        ];
        $this->assertEquals($expected, $fields);

        $fields = $this->Article->fields(false);

        $expected = array(
            'id',
            'author_id',
            'title',
            'body',
            'created',
            'modified',
            );
        $this->assertEquals($expected, $fields);
    }

    public function testSchema()
    {
        $schema = $this->Article->schema();
        $expected = [
            'type' => 'integer',
            'length' => 11,
            'default' => null,
            'null' => false,
            'key' => 'primary',
            'autoIncrement' => true
        ];
        $this->assertEquals($expected, $schema['id']);

        $idSchema = $this->Article->schema('id');
        $this->assertEquals($expected, $idSchema);
    }

    public function testHasField()
    {
        $this->assertTrue($this->Article->hasField('title'));
        $this->assertFalse($this->Article->hasField('foo'));
    }

    public function testDisplayField()
    {
        $this->assertEquals('title', $this->Article->displayField);
        $this->assertEquals('name', $this->Author->displayField);
        $this->assertEquals('id', $this->Address->displayField);
        $this->Article->hasAndBelongsToMany('Tag');
        $this->assertEquals('article_id', $this->Article->ArticlesTag->displayField);

        $sql = "CREATE TABLE foos (a VARCHAR(255) NOT NULL,b VARCHAR(255) NOT NULL,created DATETIME NOT NULL,modified DATETIME NOT NULL);";
        $this->Article->query('DROP TABLE IF EXISTS foos;');
        $this->Article->query($sql);
        $dummy = new Model(['name'=>'Foo','datasource'=>'test']);
  
        $this->expectException(Exception::class);
        $display = $dummy->displayField;
    }


    public function testMagicHasOneDefault()
    {
        $Post = new Model(array('name' => 'Post'));
        $relationship = (object) $Post->hasOne('Comment');

        $this->assertEquals('Comment', $relationship->className);

        $this->assertEquals('post_id', $relationship->foreignKey);
        $expected = array('Post.id = Comment.post_id');
        $this->assertEquals($expected, $relationship->conditions);
        $this->assertNull($relationship->fields);
        $this->assertFalse($relationship->dependent);
    }

    public function testMagicHasOneAlias()
    {
        $User = new Model(array('name' => 'User'));
        $relationship = (object) $User->hasOne('Profile', array('className' => 'UserProfile'));
        $this->assertEquals('user_id', $relationship->foreignKey);
        $expected = array('User.id = Profile.user_id');
        $this->assertEquals($expected, $relationship->conditions);
    }

    public function testMagicHasOneMerge()
    {
        $Post = new Model(array('name' => 'Post'));
        $hasOneConfig = array(
          'className' => 'FunkyComments',
          'foreignKey' => 'funky_post_id',
          'conditions' => array(
            '1 == 1',
          ),
          'fields' => array('id', 'description'),
          'dependent' => true,
        );

        // Test Merge went okay
        $relationship = $Post->hasOne('Comment', $hasOneConfig);
        $hasOneConfig['conditions'] = array(
          'Post.id = Comment.funky_post_id',
          '1 == 1',
        );

        $this->assertEquals($hasOneConfig, $relationship);
    }

    public function testMagicBelongsToDefault()
    {
        // Test Default
        $Post = new Model(array('name' => 'Post'));
        $relationship = (object) $Post->belongsTo('User');

        $this->assertEquals('User', $relationship->className);

        $this->assertEquals('user_id', $relationship->foreignKey);
        $expected = array('Post.user_id = User.id');
        $this->assertEquals($expected, $relationship->conditions);
        $this->assertNull($relationship->fields);
        $this->assertEquals('LEFT', $relationship->type);
    }

    public function testMagicBelongsToAlias()
    {
        // Test Alias Stuff
        $Post = new Model(array('name' => 'Post'));
        $relationship = (object) $Post->belongsTo('Owner', array('className' => 'User'));

        $this->assertEquals('user_id', $relationship->foreignKey);
        $expected = array('Post.user_id = Owner.id');
        $this->assertEquals($expected, $relationship->conditions);
    }

    public function testMagicBelongsToMerge()
    {
        // Test merge
        $Post = new Model(array('name' => 'Post'));
        $belongsToConfig = array(
          'alias' => 'Owner',
          'className' => 'User',
          'foreignKey' => 'owner_id',
          'conditions' => array(
            '1 == 1',
          ),
          'fields' => array('id', 'name'),
          'type' => 'INNER',
        );

        // Test Merge went okay
        $relationship = $Post->belongsTo('User', $belongsToConfig);

        $belongsToConfig['conditions'] = array(
          'Post.owner_id = User.id',
          '1 == 1',
        );

        $this->assertEquals($belongsToConfig, $relationship);
    }

    public function testMagicHasManyDefault()
    {
        // Test Default
        $Post = new Model(array('name' => 'Post'));
        $relationship = (object) $Post->hasMany('Comment');

        $this->assertEquals('Comment', $relationship->className);

        $this->assertEquals('post_id', $relationship->foreignKey);
        $this->assertNull($relationship->fields);
        $this->assertNull($relationship->order);
        $this->assertFalse($relationship->dependent);
    }

    public function testMagicHasManyAlias()
    {
        $Post = new Model(array('name' => 'Post'));
        $relationship = (object) $Post->hasMany('Comment', array('className' => 'VisitorComment'));
        $this->assertEquals('post_id', $relationship->foreignKey);
    }

    public function testMagicHasManyMerge()
    {
        $Post = new Model(array('name' => 'Post'));
        $hasManyConfig = array(
          'alias' => 'Owner',
          'className' => 'User',
          'foreignKey' => 'owner_id',
          'conditions' => array(
            'Post.id = UserComment.post_id',
          ),
          'fields' => array('id', 'title'),
          'order' => array('created ASC'),
          'dependent' => true,
          'limit' => 10,
          'offset' => 5,
         );

        // Test Merge went okay
        $relationship = $Post->hasMany('UserComment', $hasManyConfig);
        $this->assertEquals($hasManyConfig, $relationship);
    }

    public function testMagicHasAndBelongsToMany()
    {
        $Candidate = new Model(array('name' => 'Job'));
        $relationship = $Candidate->hasAndBelongsToMany('Candidate');
        $expected = array(
            'className' => 'Candidate',
            'joinTable' => 'candidates_jobs',
            'foreignKey' => 'job_id',
            'associationForeignKey' => 'candidate_id',
            'conditions' => array('CandidatesJob.candidate_id = Candidate.id'),
            'fields' => null,
            'order' => null,
            'dependent' => null,
            'limit' => null,
            'offset' => null,
            'with' => 'CandidatesJob',
            'mode' => 'replace',
          );
        $this->assertEquals($expected, $relationship);
        
        // Test Merging
        $relationship = $Candidate->hasAndBelongsToMany('Candidate', ['conditions'=>['Candidate.active'=>true]]);
        $this->assertEquals('CandidatesJob.candidate_id = Candidate.id', $relationship['conditions'][0]);
        $this->assertEquals(true, $relationship['conditions']['Candidate.active']);
    }

    public function testRelationsAgain()
    {
        $User = new Model(array('name' => 'User'));
        $User->hasOne('Profile');
        $this->assertEquals('user_id', $User->hasOne['Profile']['foreignKey']);

        $Profile = new Model(array('name' => 'Profile'));
        $Profile->belongsTo('User');
        $this->assertEquals('user_id', $Profile->belongsTo['User']['foreignKey']);

        $User = new Model(array('name' => 'User'));
        $User->hasMany('Comment');
        $this->assertEquals('user_id', $User->hasMany['Comment']['foreignKey']);

        $Ingredient = new Model(array('name' => 'Ingredient'));
        $Ingredient->hasAndBelongsToMany('Recipe');
        $this->assertEquals(
        'ingredient_id',
        $Ingredient->hasAndBelongsToMany['Recipe']['foreignKey']
      );
        $this->assertEquals(
        'recipe_id',
        $Ingredient->hasAndBelongsToMany['Recipe']['associationForeignKey']
      );
    }



    public function testFindFirst()
    {
        $result =  $this->Article->find('first');
        $this->assertInstanceOf(Entity::class, $result);
        
        $result = $this->Article->find('first', ['conditions'=>['id'=>'does-not-exist']]);
        $this->assertNull($result);
    }

    public function testFindAll()
    {
        $result =  $this->Article->find('all');
        $this->assertInstanceOf(Collection::class, $result);
        
        $result = $this->Article->find('all', ['conditions'=>['id'=>'does-not-exist']]);
        $this->assertTrue(is_array($result));
    }
    public function testFindConditions()
    {
        $result = $this->Article->find('first', ['conditions'=>['id'=>1001]]);
        $this->assertEquals(1001, $result->id);

        $result = $this->Article->find('first', ['conditions'=>['Article.id'=>1001]]);
        $this->assertEquals(1001, $result->id);
    }

    public function testFindOrder()
    {
        $result = $this->Article->find('first', ['order'=>'created DESC']);
        $this->assertEquals(1002, $result->id);

        $result = $this->Article->find('first', ['order'=>['created ASC']]);
        $this->assertEquals(1000, $result->id);
    }

    public function testFindFields()
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
        $this->assertEquals(1000, $result[1]->author_id);
        $this->assertEquals(2, $result[1]->total);
    }

    public function testFindLimit()
    {
        $result = $this->Article->find('all', ['limit'=>2]);
        $this->assertEquals(2, count($result));
    }

    public function testFindJoin()
    {
        $conditions = [
            'conditions' => ['id'=>1000],
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
       
        $this->assertEquals('Author #2', $result->author->name);
    }

    public function testFindCount()
    {
        $result = $this->Article->find('count', ['fields'=>['really_does_not_matter']]);
        $this->assertEquals(3, $result);

        $result = $this->Article->find('count', ['conditions'=>['id'=>'does-not-exist']]);
        $this->assertEquals(0, $result);
    }

    public function testFindList()
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
        # Stub Model
        $stub = $this->getMockForModel('Article', [
            'beforeFind','afterFind'
        ], ['className'=>Article::class]);
        
        $stub->expects($this->once())
        ->method('beforeFind')
        ->willReturn($this->returnArgument(0));

        $stub->expects($this->once())
        ->method('afterFind')
        ->willReturn($this->returnArgument(0));

        # Stub Behavior
        $tsStub = $this->getMock(
            'Origin\Core\Model\Behavhior\TimestampBehavior',
            ['beforeFind','afterFind']
        );

        $tsStub->expects($this->once())
        ->method('beforeFind')
        ->willReturn($this->returnArgument(0));
        
        $tsStub->expects($this->once())
        ->method('afterFind')
        ->willReturn($this->returnArgument(0));

        $stub->behaviorRegistry()->set('Timestamp', $tsStub);
        $stub->loadBehavior('Timestamp');
        $stub->enableBehavior('Timestamp');
        
        $stub->find('first');
    }

    public function testFindCallbacksHalt()
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
    }

    public function testFindCallbacksDisabled()
    {
        $stub = $this->getMockForModel('Article', [
            'beforeFind','afterFind'
        ], ['className'=>Article::class]);
        
        $stub->expects($this->never())
        ->method('beforeFind');

        $stub->expects($this->never())
        ->method('afterFind');

        $stub->find('first', ['callbacks'=>false]);
    }

    public function testFindAssociated()
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
            'conditions' => ['id'=>1000],
            'associated'=>['Author']
            ]);
       
        $this->assertEquals(1001, $result->author_id);
        $this->assertEquals(1001, $result->author->id);
        $this->assertEquals('Author #2', $result->author->name);

        $this->assertTrue($result->author->has('created'));
        $result = $this->Article->find('first', [
            'associated'=>['Author'=>['fields'=>['id','name']]]
            ]);
      
        $this->assertFalse($result->author->has('created'));

        $result = $this->Article->find('first', [
            'conditions' => ['id'=>1001],
            'associated'=>['Author'=>['associated'=>['Address']]]
            ]);
        $this->assertEquals(1000, $result->author_id);
        $this->assertEquals(1000, $result->author->id);
        $this->assertEquals(1000, $result->author->address->author_id);

        // Article id, has author  1001 and this author has no address
        $this->assertTrue($result->author->address->has('created'));
        $result = $this->Article->find('first', [
            'conditions' => ['id'=>1001],
            'associated'=>['Author'=>['associated'=>['Address'=>['fields'=>['id','author_id','description']]]]]
            ]);
        $this->assertFalse($result->author->address->has('created'));

        $result = $this->Article->Author->find('first', [
            'associated'=>['Address'=>['associated'=>'Author']],
            'conditions'=>['id'=>1000] // author id
            ]);

    
        $this->assertEquals(1000, $result->id);
        $this->assertEquals(1000, $result->address->author_id);
        $this->assertEquals(1000, $result->address->author->id);

        $this->expectException(InvalidArgumentException::class);
        $this->Article->find('first', ['associated'=>['Foo']]);
    }

    public function testExists()
    {
        $this->assertTrue($this->Article->exists(1000));
        $this->assertFalse($this->Article->exists(10000000));
    }

    public function testGet()
    {
        $result = $this->Article->get(1001);
        $this->assertEquals('Article #2', $result->title);
        $this->expectException(NotFoundException::class);
        $this->Article->get(10000000);
    }

    public function testQuery()
    {
        $result = $this->Article->query('SELECT title from articles');
        $this->assertEquals('Article #1', $result[0]['title']);
        $result = $this->Article->query('SELECT title from articles WHERE id = :id', ['id'=>1002]);
        $this->assertEquals('Article #3', $result[0]['title']);

        $result = $this->Article->query('DELETE from articles WHERE id = :id', ['id'=>1000]);
        $this->assertTrue($result);
    }

    public function testLoadBehavior()
    {
        $this->Article->loadBehavior('Timestamp', ['className'=>'Origin\Model\Behavior\TimestampBehavior']);
        $this->AssertInstanceOf(Behavior::class, $this->Article->Timestamp);
    }

    public function testEnableDisableBehavior()
    {
        $this->Article->loadBehavior('Timestamp');
        $this->assertTrue($this->Article->disableBehavior('Timestamp'));
        $this->assertTrue($this->Article->enableBehavior('Timestamp'));
    }

    public function testLoadModel()
    {
        $this->Article->loadModel('Author');
        $this->AssertInstanceOf(Model::class, $this->Article->Author);
        $this->expectException(MissingModelException::class);
        $this->Article->loadModel('Foo');
    }

    public function testValidates()
    {
        $this->Article->validate('title', 'notBlank');
        $article = $this->Article->new(['title'=>null]);
        $this->assertFalse($this->Article->validates($article));
        $article = $this->Article->new(['title'=>'Test']);
        $this->assertTrue($this->Article->validates($article));
    }

    public function testMagicMethods()
    {
        $this->assertNull($this->Article->Foo);
        $this->assertFalse(isset($this->Article->Author));
        $this->Article->belongsTo('Author');
        $this->assertInstanceOf(Model::class, $this->Article->Author);
        $this->Article->hasMany('Foo');
        $this->expectException(MissingModelException::class);
        $this->Article->Foo->find('count');
    }



    public function testAssociation()
    {
        $this->Article->hasOne('Author');
        $this->assertNotEmpty($this->Article->association('hasOne'));

        $this->Article->hasMany('Comment');
        $this->assertNotEmpty($this->Article->association('hasMany'));

        $this->Article->belongsTo('Website');
        $this->assertNotEmpty($this->Article->association('belongsTo'));
        $this->Article->hasAndBelongsToMany('Tag');
        $this->assertNotEmpty($this->Article->association('hasAndBelongsToMany'));
        
        $this->expectException(Exception::class);
        $this->Article->association('doesNotBelongToAnyButMightDo');
    }

    public function testCrud()
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

    public function testSaveField()
    {
        $this->Article->saveField(1000, 'title', 'foo');
        $article = $this->Article->get(1000);
        $this->assertEquals('foo', $article->title);
    }

    /**
     * @depends testCrud
     */
    public function testSaveValidation()
    {
        $article = $this->Article->new();
        $this->assertFalse($this->Article->save($article));

        $this->Article->validate('title', [
            'rule'=>'notBlank',
            'required'=>true,
            'on'=>'create']);
        
        $article->author_id = 1001;
        $article->title = null;
        $article->body = 'Title is blank so it should fail';
        $this->assertFalse($this->Article->save($article));
        $this->assertNotEmpty($article->errors());

        $article = $this->Article->new();
        $article->author_id = 1001;
        $article->title = 'Now this should work';
        $article->body = 'did not want to call reset';
   
        $this->assertTrue($this->Article->save($article));

        $article = $this->Article->new();
        $article->author_id = 1001;
        $article->title = 'Testing CRUD';
        $article->body = ['bad data'];
        $this->assertFalse($this->Article->save($article));
    }

    /**
     * @depends testCrud
     */
    public function testSaveCallbacks()
    {
        $stub = $this->getMockForModel('Article', [
            'beforeValidate','afterValidate','beforeSave','afterSave'
        ], ['className'=>Article::class]);
        
        $stub->expects($this->once())
        ->method('beforeValidate')
        ->willReturn($this->returnArgument(0));

        $stub->expects($this->once())
        ->method('afterValidate')
        ->willReturn($this->returnArgument(0));

        $stub->expects($this->once())
        ->method('beforeSave')
        ->willReturn($this->returnArgument(0));

        $stub->expects($this->once())
        ->method('afterSave')
        ->willReturn($this->returnArgument(0));

        # Stub Behavior
        $tsStub = $this->getMock(
            'Origin\Core\Model\Behavhior\TimestampBehavior',
            ['beforeValidate','afterValidate','beforeSave','afterSave']
        );

        $tsStub->expects($this->once())
        ->method('beforeValidate')
        ->willReturn($this->returnArgument(0));
        
        $tsStub->expects($this->once())
        ->method('afterValidate')
        ->willReturn($this->returnArgument(0));

        $tsStub->expects($this->once())
        ->method('beforeSave')
        ->willReturn($this->returnArgument(0));
        
        $tsStub->expects($this->once())
        ->method('afterSave')
        ->willReturn($this->returnArgument(0));

        $stub->behaviorRegistry()->set('Timestamp', $tsStub);
        $stub->loadBehavior('Timestamp');
        $stub->enableBehavior('Timestamp');

        $article = $stub->new();
        $article->title = 'Callback Test';
        $article->author_id = 512;
        $article->body = 'Article body goes here.';

        $this->assertTrue($stub->save($article));
    }

    /**
     * @depends testCrud
     */
    public function testSaveCallbacksValidationFail()
    {
        $stub = $this->getMockForModel('Article', [
            'beforeValidate','afterValidate','beforeSave','afterSave'
        ], ['className'=>Article::class]);
        
        $stub->expects($this->once())
        ->method('beforeValidate')
        ->willReturn(false);

        $stub->expects($this->never())
        ->method('afterValidate');

        $stub->expects($this->never())
        ->method('beforeSave');

        $stub->expects($this->never())
        ->method('afterSave');

        $article = $stub->new();
        $article->author_id = 1234;
        $article->title = 'Mocked method will return false';
        $article->body = 'Article body goes here.';

        $this->assertFalse($stub->save($article));
    }
    /**
      * @depends testCrud
      */
    public function testSaveCallbacksBeforeSaveReturnFalse()
    {
        $stub = $this->getMockForModel('Article', [
            'beforeValidate','afterValidate','beforeSave','afterSave'
        ], ['className'=>Article::class]);
        
        $stub->expects($this->once())
        ->method('beforeValidate')
        ->willReturn(true);

        $stub->expects($this->once())
        ->method('afterValidate');

        $stub->expects($this->once())
        ->method('beforeSave')
        ->willReturn(false);

        $stub->expects($this->never())
        ->method('afterSave');

        $article = $stub->new();
        $article->author_id = 1234;
        $article->title = 'Mocked method will return false';
        $article->body = 'Article body goes here.';

        $this->assertFalse($stub->save($article));
    }
    /**
      * @depends testCrud
      */
    public function testSaveCallbacksDisabled()
    {
        $stub = $this->getMockForModel('Article', [
            'beforeValidate','afterValidate','beforeSave','afterSave'
        ], ['className'=>Article::class]);
        
        $stub->expects($this->never())
        ->method('beforeValidate');

        $stub->expects($this->never())
        ->method('afterValidate');

        $stub->expects($this->never())
        ->method('beforeSave');

        $stub->expects($this->never())
        ->method('afterSave');

        $article = $stub->new();
        $article->author_id = 1234;
        $article->title = 'Mocked method will return false';
        $article->body = 'Article body goes here.';

        $this->assertTrue($stub->save($article, ['callbacks'=>false]));
    }


    public function testSaveAssociatedDisabled()
    {
        $this->Article->belongsTo('Author');
        $this->Article->hasMany('Comment');
        $data = [
            'title' => 'testNewEntity',
            'author_id' => 1234,
            'body' => 'article body goes here',
            'author' => [
                'name' => 'Jon Snow',
                'location' => 'The North',
                'rating' => 5
            ],
            'comments' => [
                ['description' => 'Save Comment #1'],
                ['description' => 'Save Comment #2']
            ]
        ];
        $article = $this->Article->new($data);
        $this->Article->save($article, ['associated'=>false]);
        $this->assertNull($article->author->id);
        $this->assertNull($article->comments[0]->id);

        $this->Author->hasOne('Address');

        $data = [
            'name' => 'Hiro Nakamura',
            'location' => 'Japan',
            'rating' => 5,
            'address' => [
                'description' => 'Last seen in Tokyo'
            ]
            ];
       
        $author = $this->Author->new($data);
        $this->Author->save($author, ['associated'=>false]);
        $this->assertNull($author->address->id);
    }

    /**
      * @depends testCrud
      */
    public function testSaveAssociatedBelongsTo()
    {
        $this->Article->belongsTo('Author');

        $data = [
            'title' => 'testSaveAssociatedBelongsTo',
            'author_id' => 1234,
            'body' => 'article body goes here',
            'author' => [
                'name' => 'Jon Snow',
                'location' => 'The North',
                'rating' => 5
            ]
        ];
        $article = $this->Article->new($data);

        $this->assertTrue($this->Article->save($article));
        $this->assertNotEmpty($article->author->id);
        $this->assertEquals($article->author_id, $article->author->id);

        # Cause failure on AssociatedObject
        $this->Article->Author->validate('name', 'email'); // i want it to fail
        $article = $this->Article->new($data);
        $this->assertFalse($this->Article->save($article));
    }
    /**
      * @depends testCrud
      */
    public function testSaveAssociatedHasOne()
    {
        $this->Author->hasOne('Address');

        $data = [
            'name' => 'Hiro Nakamura',
            'location' => 'Japan',
            'rating' => 5,
            'address' => [
                'description' => 'Last seen in Tokyo'
            ]
            ];
       

        $author = $this->Author->new($data);
  
        $this->assertTrue($this->Author->save($author));
        $this->assertNotEmpty($author->address->id);
        $this->assertEquals($author->id, $author->address->author_id);

        # Cause failure on AssociatedObject
        $this->Author->Address->validate('description', 'email'); // i want it to fail
        $article = $this->Author->new($data);
        $this->assertFalse($this->Author->save($article));
    }
    /**
      * @depends testCrud
      */
    public function testSaveAssociatedHasMany()
    {
        $this->Article->hasMany('Comment');

        $data = [
            'title' => 'testSaveAssociatedHasMany',
            'author_id' => 5678,
            'body' => 'Article body',
            'comments' => [
                ['description' => 'Save Comment #1'],
                ['description' => 'Save Comment #2']
            ]
        ];
        $article = $this->Article->new($data);
        $this->assertTrue($this->Article->save($article));
 
        $this->assertNotEmpty($article->comments[0]->id);
        $this->assertEquals($article->id, $article->comments[0]->article_id);
        $this->assertNotEmpty($article->comments[1]->id);
        $this->assertEquals($article->id, $article->comments[1]->article_id);
        $this->assertNotEquals($article->comments[0]->id, $article->comments[1]->id);

        # Cause failure on AssociatedObject
        $this->Article->Comment->validate('description', 'email'); // i want it to fail
        $article = $this->Article->new($data);
        $this->assertFalse($this->Article->save($article));
    }
    /**
      * @depends testCrud
      */
    public function testSaveAssociatedHasAndBelongsToManyPrimarykey()
    {
        $this->Article->hasAndBelongsToMany('Tag');
        $this->Article->Tag->hasAndBelongsToMany('Tag');
        
        /// Create a new Article for this test
        $data = [
            'title' => 'belongsToManyPrimaryKey',
            'author_id' => 1002,
            'body' => 'Article body'
        ];
        $article = $this->Article->new($data);
        $this->assertTrue($this->Article->save($article));
     
        $data = [
            'id' => $article->id,
            'tags' => [
              ['id' => 1001],
              ['id' => 1002]
            ]
        ];
        $article = $this->Article->new($data);
        $this->assertTrue($this->Article->save($article));

        $article = $this->Article->get($article->id, ['associated'=>['Tag']]);
        $this->assertEquals(2, count($article->tags));

        // test non existant ids
        $data = [
            'id' => $article->id,
            'tags' => [
              ['id' => 1010101],
              ['id' => 1010102]
            ]
        ];
        $article = $this->Article->new($data);
        $this->assertFalse($this->Article->save($article));
    }
    /**
      * @depends testCrud
      */
    public function testSaveAssociatedHasAndBelongsToManyDisplayField()
    {
        $this->Article->hasAndBelongsToMany('Tag');
        $this->Article->Tag->hasAndBelongsToMany('Tag');
        
        /// Create a new Article for this test
        $data = [
            'title' => 'belongsToManyDisplayField',
            'author_id' => 1002,
            'body' => 'Article body'
        ];
        $article = $this->Article->new($data);
        $this->assertTrue($this->Article->save($article));
     
        $data = [
            'id' => 1000, // Article Id
            'tags' => [
                ['title' => 'Tag #1'],
                ['title' => 'Featured']
             ]
          ];
        $article = $this->Article->new($data);
       
        $this->assertTrue($this->Article->save($article));
        $this->assertEquals(1000, $article->tags[0]->id);
        $this->assertEquals(1003, $article->tags[1]->id);
        
        $article = $this->Article->get($article->id, ['associated'=>['Tag']]);
        $this->assertEquals(2, count($article->tags));
    }

    public function testSaveAssociatedHasAndBelongsToManyUnkown()
    {
        $this->Article->hasAndBelongsToMany('Tag');
        $this->Article->Tag->hasAndBelongsToMany('Tag');
        
         
        $data = [
            'id' => 1000,
            'tags' => [
              ['drink' => 'cola']
            ]
        ];
        $article = $this->Article->new($data);
        $this->assertFalse($this->Article->save($article));
    }

    /**
      * @depends testCrud
      */
    public function testSaveAssociatedHasAndBelongsToManyAppend()
    {
        $this->Article->hasAndBelongsToMany('Tag', ['mode'=>'append']);
        $this->Article->Tag->hasAndBelongsToMany('Tag', ['mode'=>'append']);

        $article = $this->Article->get(1000, ['associated'=>['Tag']]);
     
        // test non existant ids
        $data = [
            'id' => $article->id,
            'tags' => [
              ['id' => 1000],
            ]
        ];
        $article = $this->Article->new($data);
        $this->assertTrue($this->Article->save($article));

        $article = $this->Article->get($article->id, ['associated'=>['Tag']]);
        $this->assertEquals(3, count($article->tags));

        $data = [
            'id' => 1001,
            'tags' => [
              ['title' => 'Tag #1'],
              ['title' => 'Tag #2'],
            ]
        ];
        $article = $this->Article->new($data);
        $this->assertTrue($this->Article->save($article));
        $article = $this->Article->get($article->id, ['associated'=>['Tag']]);
        $this->assertEquals(2, count($article->tags));
    }

    public function testNewEntities()
    {
        $data = [
            ['title'=>'Dummy Article #1','author_id'=>5432,'body'=>'...'],
            ['title'=>'Dummy Article #2','author_id'=>6789,'body'=>'...']
        ];
        $articles = $this->Article->newEntities($data);
        $this->assertEquals('Dummy Article #1', $articles[0]->title);
        $this->assertEquals('Dummy Article #2', $articles[1]->title);
    }
    public function testSaveMany()
    {
        $data = [
            ['title'=>'Dummy Article #1','author_id'=>5432,'body'=>'...'],
            ['title'=>'Dummy Article #2','author_id'=>6789,'body'=>'...'],
            ['title'=>'Dummy Article #3','author_id'=>1212,'body'=>'...']
        ];
        $articles = $this->Article->newEntities($data);
        $this->assertTrue($this->Article->saveMany($articles));
        $this->assertNotEmpty($articles[0]->id);
        $this->assertNotEmpty($articles[1]->id);
        $this->assertNotEmpty($articles[2]->id);
        $this->assertNotEquals($articles[0]->id, $articles[1]->id);

        $stub = $this->getMockForModel('Article', ['save'], ['className'=>Article::class]);

        $stub->expects($this->any())
        ->method('save')
        ->willReturn(false);
        $articles = $this->Article->newEntities($data);
        $this->assertFalse($stub->saveMany($articles));
    }

    public function testUpdateAll()
    {
        $result = $this->Article->updateAll(['title'=>'Updated Article'], ['id !='=> 1000]);
        $this->assertTrue($result);
        $count = $this->Article->find('count', ['conditions'=>['title'=>'Updated Article']]);
        $this->assertEquals(2, $count);
    }
    public function testDelete()
    {
        $this->Article->hasMany('Comment');
        $this->Article->hasAndBelongsToMany('Tag');

        $article = $this->Article->get(1000, ['associated'=>['Comment','Tag']]);
        $comments = count($article->comments);
        $tags = count($article->tags);

        $this->assertTrue($this->Article->delete($article));
        $this->assertEquals($comments, $this->Article->Comment->find('count', ['conditions'=>['article_id'=>1000]])); // did not delete
       $this->assertNotEquals($tags, $this->Article->ArticlesTag->find('count', ['conditions'=>['article_id'=>1000]])); // Delete always

       $this->Article->hasMany('Comment', ['dependent'=>true]);
        $article = $this->Article->get(1002, ['associated'=>['Comment','Tag']]);
        $this->assertGreaterThan(0, count($article->comments));
        $this->assertTrue($this->Article->delete($article));
        $this->assertEquals(0, $this->Article->Comment->find('count', ['conditions'=>['article_id'=>1002]])); // did not delete
        
        // Disable Cascade
        $article = $this->Article->new(['id'=>1000,'title'=>'Adding Article Back','author_id'=>1234,'description'=>'Because it has comments']);
        $this->assertTrue($this->Article->save($article));
        $this->assertTrue($this->Article->delete($article, false));
        $this->assertEquals($comments, $this->Article->Comment->find('count', ['conditions'=>['article_id'=>1000]]));
        
        // Test Delete Not Exists

        $article = $this->Article->new();
        $article->id = 124;
        $this->assertFalse($this->Article->delete($article));
    }
 
    public function testDeleteCallbacks()
    {
        $article = $this->Article->find('first');

        # Stub Model
        $stub = $this->getMockForModel('Article', [
            'beforeDelete','afterDelete'
        ], ['className'=>Article::class]);
        
        $stub->expects($this->once())
        ->method('beforeDelete')
        ->willReturn($this->returnArgument(0));

        $stub->expects($this->once())
        ->method('afterDelete')
        ->willReturn($this->returnArgument(0));

        # Stub Behavior
        $tsStub = $this->getMock(
            'Origin\Core\Model\Behavhior\TimestampBehavior',
            ['beforeDelete','afterDelete']
        );

        $tsStub->expects($this->once())
        ->method('beforeDelete')
        ->willReturn($this->returnArgument(0));
        
        $tsStub->expects($this->once())
        ->method('afterDelete')
        ->willReturn($this->returnArgument(0));

        $stub->behaviorRegistry()->set('Timestamp', $tsStub);
        $stub->loadBehavior('Timestamp');
        $stub->enableBehavior('Timestamp');
        
        
        $this->assertTrue($stub->delete($article));
    }

    public function testDeleteAll()
    {
        $count = $this->Article->find('count');
        $this->assertTrue($this->Article->deleteAll(['id !=' => 1000]));
        $this->assertNotEquals($count, $this->Article->find('count'));
    }

    /**
     * In the marshalling test we already cover, however here
     * is extra brief test on real usage
     */
    public function testNew()
    {
        $this->Article->belongsTo('Author');
        $this->Article->hasMany('Comment');
        $data = [
            'title' => 'testNewEntity',
            'author_id' => 1234,
            'body' => 'article body goes here',
            'author' => [
                'name' => 'Jon Snow',
                'location' => 'The North',
                'rating' => 5
            ],
            'comments' => [
                ['description' => 'Save Comment #1'],
                ['description' => 'Save Comment #2']
            ]
        ];

        $article = $this->Article->new($data);
        $this->assertInstanceOf(Entity::class, $article);
        $this->assertInstanceOf(Entity::class, $article->author);
        $this->assertInstanceOf(Entity::class, $article->comments[0]);

        // disable associations
        $article = $this->Article->new($data, ['associated'=>false]);
        $this->assertNotEmpty($article->title);
        $this->assertTrue(is_array($article->author)); // test associated

        $article = $this->Article->new($data, ['fields'=>['title']]);
        $this->assertNotEmpty($article->title);
        $this->assertEmpty($article->body);
        $this->assertEmpty($article->author); // test associated

        $article = $this->Article->new($data, ['fields'=>['title','author'],'associated'=>['Author']]);
        $this->assertNotEmpty($article->title);
        $this->assertNotEmpty($article->author->location);

        $article = $this->Article->new($data, ['fields'=>['title','author'],'associated'=>['Author'=>['fields'=>['name']]]]);
        $this->assertNotEmpty($article->title);
        $this->assertNotEmpty($article->author->name);
        $this->assertEmpty($article->author->location);
    }

    public function testNewPatchCallback()
    {
        $stub = $this->getMockForModel('Article', [
            'beforeMarshal'
        ], ['className'=>Article::class]);

        $stub->expects($this->exactly(2))
        ->method('beforeMarshal')
        ->willReturn($this->returnArgument(0));

        $data = ['title'=>'Stubbing'];
        $entity = $stub->new($data);
        $stub->patch($entity, $data);
    }

    public function testTransactions()
    {
        $data = [
            'title' => 'Transactions',
            'author_id' => 1234,
            'body' => 'article body goes here',
        ];

        $article = $this->Article->new($data);

        # Check Transactions actually work
        $this->Article->begin();
        $this->assertTrue($this->Article->save($article, ['transaction'=>false]));
        $this->Article->rollback();
        $this->assertEquals(3, $this->Article->find('count'));

        $article = $this->Article->new($data);
        $this->Article->begin();
        $this->assertTrue($this->Article->save($article, ['transaction'=>false]));
        $this->Article->commit();
        $this->assertEquals(4, $this->Article->find('count'));
    }

    public function testTransactionsCalled()
    {
        $data = [
            'title' => 'Transactions',
            'author_id' => 1234,
            'body' => 'article body goes here',
        ];

        $stub = $this->getMockForModel('Article', [
            'begin','rollback','processSave'
        ], ['className'=>Article::class]);

        $stub->expects($this->once())
        ->method('begin');

        $stub->expects($this->once())
        ->method('processSave')
        ->willReturn(false);

        $stub->expects($this->once())
        ->method('rollback');

        $article = $this->Article->new($data);
        $this->assertFalse($stub->save($article));

        ## Test Commit is called
        $stub = $this->getMockForModel('Article', [
            'begin','commit','processSave'
        ], ['className'=>Article::class]);

        $stub->expects($this->once())
        ->method('begin');

        $stub->expects($this->once())
        ->method('processSave')
        ->willReturn(true);

        $stub->expects($this->once())
        ->method('commit');

        $article = $this->Article->new($data);
        $this->assertTrue($stub->save($article));
    }

    public function testTransactionsDisabled()
    {
        $data = [
            'title' => 'Transactions',
            'author_id' => 1234,
            'body' => 'article body goes here',
        ];

        $article = $this->Article->new($data);
        
        # Test Disable
        $stub = $this->getMockForModel('Article', [
            'begin','commit','processSave'
        ], ['className'=>Article::class]);
        $stub->expects($this->once())->method('processSave')->willReturn(true);
        $stub->expects($this->never())->method('begin');
        $stub->expects($this->never())->method('commit');
        $this->assertTrue($stub->save($article, ['transaction'=>false]));

        $stub = $this->getMockForModel('Article', [
            'begin','rollback','processSave'
        ], ['className'=>Article::class]);
        $stub->expects($this->once())->method('processSave')->willReturn(false);
        $stub->expects($this->never())->method('begin');
        $stub->expects($this->never())->method('rollback');
        $this->assertFalse($stub->save($article, ['transaction'=>false]));
    }
}
