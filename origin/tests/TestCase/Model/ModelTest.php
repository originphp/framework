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

use App\Model\AppModel;
use Origin\Model\Model;
use Origin\Model\ConnectionManager;
use Origin\Model\ModelRegistry;
use Origin\Model\Entity;
use Origin\Model\Behavior\Behavior;
use Origin\Model\Exception\MissingModelException;
use Origin\Exception\NotFoundException;

class Article extends AppModel
{
    public function initialize(array $config)
    {
        $this->hasMany('Comment');
        $this->belongsTo('User');
        $this->hasAndBelongsToMany('Tag');
    }
}

class Comment extends AppModel
{
    public function initialize(array $config)
    {
        $this->belongsTo('Article');
    }
}

class Tag extends AppModel
{
    public function initialize(array $config)
    {
        $this->hasAndBelongsToMany('Article');
    }
}

class Profile extends AppModel
{
    public function initialize(array $config)
    {
        $this->belongsTo('User');
    }
}

class User extends AppModel
{
    public function initialize(array $config)
    {
        $this->hasOne('Profile');
    }
}

class BehaviorTesterBehavior extends Behavior
{
}

class ModelTest extends \PHPUnit\Framework\TestCase
{
    public function tearDown()
    {
        ModelRegistry::clear();
    }
    /**
     * @todo migrate to Fixtures
     */
    public static function setUpBeforeClass()
    {
        $sql = file_get_contents(ORIGIN.DS.'tests'.DS.'TestCase/Model/schema.sql');
        $statements = explode(";\n", $sql);

        $connection = ConnectionManager::get('test');

        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $connection->execute($statement);
            }
        }
    }

    public static function tearDownAfterClass()
    {
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
        $Article = new Model(array('name' => 'Article', 'datasource' => 'test'));
        $fields = $Article->fields();

        $expected = array(
          'Article.id',
          'Article.user_id',
          'Article.title',
          'Article.slug',
          'Article.body',
          'Article.published',
          'Article.created',
          'Article.modified',
          );
        $this->assertEquals($expected, $fields);

        $fields = $Article->fields(false);

        $expected = array(
            'id',
            'user_id',
            'title',
            'slug',
            'body',
            'published',
            'created',
            'modified',
            );
        $this->assertEquals($expected, $fields);
    }

    public function testSchema()
    {
        $Article = new Model(array('name' => 'Article', 'datasource' => 'test'));
        $schema = $Article->schema();
        $expected = array(
            'type' => 'int',
            'length' => 11,
            'precision' => null,
            'default' => null,
            'null' => null,
            'key' => 'primary',
            'autoIncrement' => true,
            'unsigned' => false
      );
        $this->assertEquals($expected, $schema['id']);
        $idSchema = $Article->schema('id');
        $this->assertEquals($expected, $idSchema);
    }

    public function testHasField()
    {
        $Article = new Model(array('name' => 'Article', 'datasource' => 'test'));
        $this->assertTrue($Article->hasField('title'));
        $this->assertFalse($Article->hasField('foo'));
    }

    public function testDetectDisplayField()
    {
        $Article = $this->getMockModel(Article::class, array('detectDisplayField'));

        $Article->expects($this->once())
        ->method('detectDisplayField')
        ->willReturn('id');

        $displayField = $Article->displayField;
    }

    public function testnew()
    {
        $data = [
          'id' => 1004,
          'name' => 'EntityName',
        ];

        $Article = new Model(array('name' => 'Article', 'datasource' => 'test'));
        $Entity = $Article->new($data);
        $this->assertEquals(1004, $Entity->id);
        $this->assertEquals('EntityName', $Entity->name);
        $this->assertEquals('Article', $Entity->name());
    }

    public function testNewEntities()
    {
        $data = [
        ['name'=>'Entity 1'],
        ['name'=>'Entity 2'],
      ];
        $Article = new Model(array('name' => 'Article', 'datasource' => 'test'));
        $entities = $Article->newEntities($data);
        $this->assertEquals('Entity 1', $entities[0]->name);
        $this->assertEquals('Entity 2', $entities[1]->name);
    }

    public function testnewBelongsTo()
    {
        $data = array(
        'id' => 1005,
        'author' => array(
          'name' => 'Amanda',
        ),
      );
        $Article = new Model(array('name' => 'Article', 'datasource' => 'test'));
        $Article->Author = new Model(array('name' => 'User','alias'=>'Author', 'datasource' => 'test'));
        $Article->belongsTo('Author');
        $Entity = $Article->new($data, ['associated'=>['Author']]);
        $this->assertTrue($Entity->has('author'));
        $this->assertTrue($Entity->author->has('name'));
        $this->assertEquals('Amanda', $Entity->author->name);
    }

    public function testCreateEntityHasMany()
    {
        $data = array(
        'id' => 1005,
        'comments' => array(
          array('comment' => 'nice article'),
          array('comment' => 'thanks for writing this'),
        ),
      );
        $Article = new Model(array('name' => 'Article', 'datasource' => 'test'));
        $Article->Comment = new Model(array('name' => 'Comment', 'datasource' => 'test'));
        $Article->hasMany('Comment');

        $Entity = $Article->new($data, ['associated'=>['Comment']]);
        $this->assertTrue($Entity->has('comments'));
        $this->assertEquals(2, count($Entity->comments));

        $this->assertEquals('nice article', $Entity->comments[0]->comment);
        $this->assertEquals('thanks for writing this', $Entity->comments[1]->comment);
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
        $this->assertNull($relationship->order);
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
          'order' => array('created ASC'),
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
        $this->assertNull($relationship->order);
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
          'order' => array('created ASC'),
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

    public function testFindCount()
    {
        $Article = new Article(['datasource' => 'test']);
        $count = $Article->find('count');
        $this->assertNotNull($count);
    }

    public function testGet()
    {
        $Article = new Article(['datasource' => 'test']);
        $this->assertNotEmpty($Article->get(2));
        $this->expectException(NotFoundException::class);
        $Article->get(1024);
    }

    public function testFindList()
    {
        $Article = new Article(['datasource' => 'test']);

        ///['a','b','c'] or ['a'=>'b'] or ['c'=>['a'=>'b']]
        $expected = array('First Post', 'Second Post', 'Third Post');
        $result = $Article->find('list', ['fields' => array('title')]);
        $this->assertEquals($expected, $result);

        $expected = array('first-post' => 'First Post', 'second-post' => 'Second Post', 'third-post' => 'Third Post');
        $result = $Article->find('list', ['fields' => array('slug', 'title')]);
        $this->assertEquals($expected, $result);

        $expected = array(
        1 => array('first-post' => 'First Post'),
        0 => array('second-post' => 'Second Post', 'third-post' => 'Third Post'),
      );
        $result = $Article->find('list', ['fields' => array('slug', 'title', 'published')]);

        $this->assertEquals($expected, $result);
    }

    public function testFindFirst()
    {
        $Article = new Article(['datasource' => 'test']);
        ModelRegistry::set('Article', $Article);
        ModelRegistry::set('User', new User(array('datasource' => 'test')));
        ModelRegistry::set('Comment', new Comment(array('datasource' => 'test')));
        ModelRegistry::set('Tag', new Tag(array('datasource' => 'test')));
        ModelRegistry::set('Profile', new Profile(array('datasource' => 'test')));

        // Only fetch this domain
        $article = $Article->find('first');
        $this->assertEquals('first-post', $article->slug);
        $objectVars = get_object_vars($article);
        $this->assertArrayNotHasKey('user', $objectVars);
        $this->assertArrayNotHasKey('comments', $objectVars);

        $article = $Article->find('first', ['associated'=>['User']]);
        $this->assertEquals('first-post', $article->slug);

        $this->assertTrue($article->has('user'));
        $this->assertFalse($article->has('comments'));

        // Fetch record with belongsTo/hasOne/and Has Many
        $article = $Article->find('first', ['associated'=>['User','Comment','Tag']]);
        $this->assertEquals('first-post', $article->slug);
        $objectVars = $article->properties();

        $this->assertContains('user', $objectVars);
        $this->assertContains('comments', $objectVars);
        $this->assertContains('tags', $objectVars);

        $objectVars = $article->comments[0]->properties();
        $this->assertFalse(in_array('article', $objectVars));

        // Fetch record with belongsTo/hasOne/and Has many and related related
        $article = $Article->find('first', ['associated'=>['User','Comment'=>['associated'=>['Article']],'Tag']]);
        $this->assertEquals('first-post', $article->slug);
        $objectVars = $article->properties();
        $this->assertContains('comments', $objectVars);

        $objectVars = $article->comments[0]->properties();
        $this->assertContains('article', $objectVars);
    }

    public function testFindAll()
    {
        $Article = new Model(array('name' => 'Article', 'datasource' => 'test'));
        $this->assertEquals([], $Article->find('all', ['conditions'=>['title'=>'Foo']]));
        $results = $Article->find('all');
        $this->assertEquals(3, count($results));
    }

    public function testExists()
    {
        $Article = new Article(['datasource' => 'test']);

        $this->assertTrue($Article->exists(1));
        $this->assertFalse($Article->exists(1024));
    }

    /**
     * [getMockModel description].
     *
     * @param string $model   App\Model\Article or Article::class
     * @param array  $methods [description]
     *
     * @return [type] [description]
     */
    public function getMockModel(string $model, array $methods = array())
    {
        list($namespace, $class) = namespaceSplit($model);

        $config = array('name' => $class, 'datasource' => 'test');

        return $this->getMockBuilder($model)
                 ->setMethods($methods)
                 ->setConstructorArgs(array($config))
                 ->getMock();
    }

    public function getMock(string $class, array $methods = array(), array $arguments = [])
    {
        if (empty($arguments)) {
            return $this->getMockBuilder($class)
                   ->getMock();
        }

        return $this->getMockBuilder($class)
                 ->setConstructorArgs($arguments)
                 ->getMock();
    }
    /**
     * Depends testExists.
     */
    public function testSaveBeforeFail()
    {
        $methods = array('beforeSave');
        $Article = $this->getMockModel(Article::class, $methods);
        $record = new Entity(array('title' => 'Foo'));

        $Article->expects($this->once())
          ->method('beforeSave')
          ->willReturn(false);

        $this->assertFalse($Article->save($record));
    }

    /**
     * Depends testExists.
     */
    public function testSaveBeforeValidateFail()
    {
        $methods = array('beforeValidate');
        $Article = $this->getMockModel(Article::class, $methods);

        $Article->expects($this->once())
          ->method('beforeValidate')
          ->willReturn(false);

        $this->assertFalse($Article->save(new Entity(array('title' => 'Foo'))));
    }

    /**
     * Depends testExists.
     */
    public function testSaveValidatesFail()
    {
        $methods = array('validates');
        $Article = $this->getMockModel(Article::class, $methods);

        $Article->expects($this->once())
          ->method('validates')
          ->willReturn(false);

        $article = new Entity(array('title' => 'Foo'));

        $this->assertFalse($Article->save($article));
    }

    /**
     * //Depends testExists.
     */
    public function testSave()
    {
        $methods = array('beforeSave', 'beforeValidate', 'validates', 'afterSave', 'afterValidate');
        $Article = $this->getMockModel(Article::class, $methods);
        $methods = array('beforeSave', 'afterSave', 'beforeValidate', 'afterValidate');

        $article = new Entity(array(
          'user_id' => 3, 'title' => 'Save Test',
          'body' => 'testing save',
          'slug' => 'test-save',
          'created' => date('Y-m-d'),
          'modified' => date('Y-m-d'),
          'marker' => true
        ));

        $Article->expects($this->once())
          ->method('beforeValidate')
          ->willReturn(true);

        $Article->expects($this->once())
          ->method('validates')
          ->willReturn(true);

        $Article->expects($this->once())
          ->method('beforeSave')
          ->willReturn(true);

        $Article->expects($this->once())
          ->method('afterSave');

        $Article->expects($this->once())
          ->method('afterValidate');

        $this->assertNull($Article->id);
        $this->assertTrue($Article->save($article));
        $this->assertNotNull($Article->id);
    }

    public function testSaveBadData()
    {
        $Article = new Article(['datasource' => 'test']);
        $entity = $Article->new(['title'=>'testSaveBadData']);
        $entity->user_id = [];
        $this->assertFalse($Article->save($entity));
    }

    public function testDelete()
    {
        $methods = array('beforeDelete', 'afterDelete', 'deleteDependent', 'deleteHABTM');
        $Article = $this->getMockModel(Article::class, $methods);
    
        $this->assertTrue($Article->exists(1));

        $Article->expects($this->once())
          ->method('beforeDelete')
          ->willReturn(true);

        $Article->expects($this->once())
            ->method('deleteDependent')
            ->willReturn(true);

        $Article->expects($this->once())
          ->method('deleteHABTM')
          ->willReturn(true);

        $Article->expects($this->once())
              ->method('afterDelete')
              ->willReturn(true);

        $this->assertTrue($Article->delete(1, true));
        $this->assertFalse($Article->exists(1));
    }

    public function testDeleteNotExists()
    {
        $methods = array('exists');
        $Article = $this->getMockModel(Article::class, $methods);

        $Article->expects($this->once())
          ->method('exists')
          ->willReturn(false);

        $this->assertFalse($Article->delete(1));
    }

    public function testDeleteCallbacksDisabled()
    {
        $methods = array('beforeDelete', 'afterDelete', 'exists', 'deleteDependent', 'deleteHABTM');
        $Article = $this->getMockModel(Article::class, $methods);

        $Article->expects($this->once())
          ->method('exists')
          ->willReturn(true);

        $Article->expects($this->never())
            ->method('beforeDelete');

        $Article->expects($this->once())
            ->method('deleteDependent');

        $Article->expects($this->once())
          ->method('deleteHABTM');

        $Article->expects($this->never())
          ->method('afterDelete');

        $Article->delete(1, true, false);
    }

    public function testDeleteCascadeDisabled()
    {
        $methods = array('exists', 'deleteDependent', 'deleteHABTM');
        $Article = $this->getMockModel(Article::class, $methods);

        $Article->expects($this->once())
          ->method('exists')
          ->willReturn(true);

        $Article->expects($this->never())
            ->method('deleteDependent');

        $Article->expects($this->once())
          ->method('deleteHABTM');

        $Article->delete(1, false, true);
    }

    public function testDeleteAll()
    {
        $Article = new Model(array('name' => 'Article', 'datasource' => 'test'));

        $this->assertFalse($Article->deleteAll()); //

        $article = $Article->get(2);
        $this->assertNotEmpty($article);
        $this->assertTrue($Article->deleteAll(['id' => $article->id]));
        $Article->save($article); // Add back
    }

    public function testDeleteAllCallbacksEnabled()
    {
        $Article = new Model(array('name' => 'Article', 'datasource' => 'test'));
        $article = $Article->get(2);
        $this->assertNotEmpty($article);
        $this->assertTrue($Article->deleteAll(['id' => $article->id]), true, true);
        $Article->save($article); // Add back
    }

    public function testSaveManyValidationErrors()
    {
        $methods = array('validates');
        $Article = $this->getMockModel(Article::class, $methods);

        $Article->expects($this->exactly(2))
        ->method('validates')
        ->willReturn(false);

        $rows = array(
          new Entity(array('title' => 'title #1')),
          new Entity(array('title' => 'title #2')),
        );
        $this->assertFalse($Article->saveMany($rows, array('transaction' => false)));
    }

    public function testSaveMany()
    {
        $Article = new Model(array('name' => 'Article','datasource'=>'test'));

        $methods = array('save');
        $Article = $this->getMockModel(Article::class, $methods);

        $Article->expects($this->exactly(2))
        ->method('save')
        ->willReturn(true);

        $rows = array(
          new Entity(array('title' => 'title #1')),
          new Entity(array('title' => 'title #2')),
        );
        $this->assertTrue($Article->saveMany($rows, array('transaction' => false)));
        $this->assertEmpty($Article->validationErrors);
    }

    public function testSaveHABTMIsCalled()
    {
        $methods = ['saveHABTM'];
        $Article = $this->getMockModel(Article::class, $methods);
        $Article->Tag = new Model(['name' => 'Tag', 'datasource' => 'test']);
        $data = $Article->new(array(
        'id' => 1,
        'tags' =>  [
          ['title' => 'testSaveHABTMIsCalled'],
        ],
       ));

        $Article->expects($this->once())
          ->method('saveHABTM')
          ->willReturn(true);

        $Article->save($data);
    }

    public function testSaveHABTMDisplayField()
    {
        $Article = new Model(array('name' => 'Article', 'datasource' => 'test'));
        $Article->Tag = new Model(array('name' => 'Tag', 'datasource' => 'test'));

        $Article->hasAndBelongsToMany('Tag');

        $Article->ArticlesTag->deleteAll(['article_id' => 1]);

        // Save by DisplayField
        $tags = array(
          ['title' => 'HABTM Tag #1'],
          ['title' => 'HABTM Tag #2'],
        );
        $data = $Article->new(array(
        'id' => 1,
        'tags' => $tags,
        ), ['associated'=>['Tag']]);
       
        $conditions = array(
          'conditions' => array('article_id' => 1),
        );
        $this->assertTrue($Article->save($data));
        $tags = $Article->Tag->find('all', array('conditions' => array('OR' => $tags)));

        $this->assertEquals(2, count($tags));
        $this->assertEquals(2, $Article->ArticlesTag->find('count', $conditions));

        return $tags;
    }

    /**
     * @depends testSaveHABTMDisplayField
     */
    public function testSaveHABTMPrimaryKey($tags)
    {
        $Article = new Model(array('name' => 'Article', 'datasource' => 'test'));
        $Article->Tag = new Model(array('name' => 'Tag', 'datasource' => 'test'));

        $Article->hasAndBelongsToMany('Tag');

        //Save by ID
        $Article->ArticlesTag->deleteAll(['article_id' => 2]);
        $data = $Article->new(array(
        'id' => 2,
        'tags' => array(
          ['id' => $tags[0]->id],
          ['id' => $tags[1]->id],
        ),
      ), ['associated'=>['Tag']]);
        $this->assertTrue($Article->save($data));

        $conditions = array(
        'conditions' => array('article_id' => 2),
      );
        $this->assertEquals(2, $Article->ArticlesTag->find('count', $conditions));
    }

    /**
     * @depends testSaveHABTMDisplayField
     */
    public function testSaveHABTMReplace()
    {
        $Article = new Model(array('name' => 'Article', 'datasource' => 'test'));
        $Article->Tag = new Model(array('name' => 'Tag', 'datasource' => 'test'));

        $Article->hasAndBelongsToMany('Tag');
        $conditions = ['article_id' => 1];

        $Article->ArticlesTag->deleteAll($conditions);

        $Article->query('ALTER TABLE articles_tags ADD id INT PRIMARY KEY AUTO_INCREMENT;');
        $Article->query('ALTER TABLE articles_tags ADD COLUMN test INT(1) default 0');
        $data = $Article->new(array(
        'id' => 1,
        'tags' => array(
          ['title' => 'HABTM Tag #1'],
          ['title' => 'HABTM Tag #2'],
        ),
      ), ['associated'=>['Tag']]);

        $this->assertTrue($Article->save($data));
        $Article->getConnection()->update('articles_tags', ['test' => 1]);
        //$conditions['test'] = 1;

        $data = $Article->new(array(
        'id' => 1,
        'tags' => array(
          ['title' => 'HABTM Tag #2'],
          ['title' => 'HABTM Tag #3'],
        ),
      ), ['associated'=>['Tag']]);

        $this->assertTrue($Article->save($data));
        $result = $Article->ArticlesTag->find('list', array('conditions' => $conditions, 'fields' => array('tag_id')));
        $this->assertEquals([4, 5], $result);
    }

    /**
     * @depends testSaveHABTMReplace
     *
     * @return [type] [description]
     */
    public function testSaveHABTMAppend()
    {
        $Article = new Model(array('name' => 'Article', 'datasource' => 'test'));
        $Article->Tag = new Model(array('name' => 'Tag', 'datasource' => 'test'));

        $Article->hasAndBelongsToMany('Tag', array('mode' => 'append'));
        $conditions = ['article_id' => 1];

        $Article->ArticlesTag->deleteAll($conditions);

        $data = $Article->new(array(
        'id' => 1,
        'tags' => array(
          ['title' => 'HABTM Tag #1'],
          ['title' => 'HABTM Tag #2'],
        ),
      ), ['associated'=>['Tag']]);

        $this->assertTrue($Article->save($data));
        $Article->getConnection()->update('articles_tags', ['test' => 1]);
        //$conditions['test'] = 1;

        $data = $Article->new(array(
        'id' => 1,
        'tags' => array(
          ['title' => 'HABTM Tag #2'],
          ['title' => 'HABTM Tag #3'],
        ),
      ), ['associated'=>['Tag']]);

        $this->assertTrue($Article->save($data));
        $result = $Article->ArticlesTag->find('list', array('conditions' => $conditions, 'fields' => array('tag_id', 'test')));
        $this->assertEquals([4 => 1, 5 => 0], $result);
    }

    public function testSaveAssociatedHasOne()
    {
        $User = new Model(array('name' => 'User', 'datasource' => 'test'));
        $User->Profile = new Model(array('name' => 'Profile', 'datasource' => 'test'));

        $User->hasOne('Profile');

        $data = $User->new(array(
        'name' => 'Dave',
        'email' => 'dave@example.com',
        'password' => 'secret',
        'profile' => array(
          // user id will be inserted automatically
          'name' => 'Developer (SAHO)',
        ),
      ), ['associated'=>['Profile']]);

        $this->assertTrue($User->save($data, ['associated'=>['Profile']]));
        $this->assertTrue($User->exists($User->id));
        $this->assertTrue($User->Profile->exists($User->Profile->id));
    }

    public function testSaveAssociatedBelongsTo()
    {
        $Profile = new Model(array('name' => 'Profile', 'datasource' => 'test'));
        $Profile->User = new Model(array('name' => 'User', 'datasource' => 'test'));

        $Profile->belongsTo('User');

        $data = $Profile->new(array(
        'name' => 'Developer (SABT)',
        'user' => array(
          'name' => 'Claire',
          'email' => 'claire@example.com',
          'password' => 'secret',
        ),
      ), ['associated'=>['User']]);

        $this->assertTrue($Profile->save($data, ['associated'=>['User']]));
        $this->assertTrue($Profile->exists($Profile->id));
        $this->assertTrue($Profile->User->exists($Profile->User->id));
    }

    public function testSaveAssociatedHasMany()
    {
        $Article = new Model(array('name' => 'Article', 'datasource' => 'test'));
        $Article->Comment = new Model(array('name' => 'Comment', 'datasource' => 'test'));

        $Article->hasMany('Comment');

        $data = $Article->new(array(
        'user_id' => 1,
        'title' => 'Save Associated Has Many',
        'slug' => 'save-associated-has-many',
        'body' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
        'comments' => array(
          array('body' => 'comment #1 for saveAssociated'),
          array('body' => 'comment #2 for saveAssociated'),
          array('body' => 'comment #3 for saveAssociated'),
        ),
      ), ['associated'=>['Comment']]);
        $count = $Article->Comment->find('count');
        $this->assertTrue($Article->save($data, ['associated'=>['Comment']]));
        $this->assertTrue($Article->exists($Article->id));
        $this->assertTrue($Article->Comment->exists($Article->Comment->id));
        $this->assertEquals($count + 3, $Article->Comment->find('count'));
    }

    public function testSaveField()
    {
        $Article = new Model(array('name' => 'Article', 'datasource' => 'test'));
        $article = $Article->find('first');
        $this->assertEquals('Second Post', $article->title); // # sanity check
    
        $this->assertTrue($Article->saveField($article->id, 'title', 'testSaveField'));
        $article = $Article->find('first');
        $this->assertEquals('testSaveField', $article->title);
    }

    public function testUpdateAll()
    {
        $Article = new Model(array('name' => 'Article', 'datasource' => 'test'));
        $count = $Article->find('count', array('conditions' => ['published' => 1]));
     
        $this->assertTrue($Article->updateAll(['published' => 1], ['published' => 0]));
        $count = $Article->find('count', array('conditions' => ['published' => 1]));
        $this->assertEquals(4, $count);
    }

    public function testQuery()
    {
        $Article = new Model(array('name' => 'Article', 'datasource' => 'test'));
        $result = $Article->query('SELECT title FROM articles');
        $this->assertTrue(is_array($result));
        $this->assertNotEmpty($result);
        $sql = 'CREATE TABLE guests (
id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
firstname VARCHAR(30) NOT NULL,
lastname VARCHAR(30) NOT NULL
)';
        $this->assertTrue($Article->query($sql));

        $this->assertTrue($Article->query('DROP TABLE guests'));
    }

    public function testSaveAssociated()
    {
        $Article = new Model(array('name' => 'Article', 'datasource' => 'test'));
        $Article->Comment = new Model(array('name' => 'Comment', 'datasource' => 'test'));

        $Article->hasMany('Comment');

        $data = $Article->new(array(
        'user_id' => 1,
        'title' => 'Delete Dependent',
        'slug' => 'delete-dependent',
        'body' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
        'comments' => array(
          array('body' => 'comment #1 for deleteDependent'),
          array('body' => 'comment #2 for deleteDependent'),
          array('body' => 'comment #3 for deleteDependentd'),
        ),
      ), ['associated'=>['Comment']]);
        $this->assertTrue($Article->save($data, ['associated'=>['Comment']]));
        return $Article->id;
    }

    /**
     * @depends testSaveAssociated
     */
    public function testDeleteDependent($articleId)
    {
        $Article = new Model(array('name' => 'Article', 'datasource' => 'test'));
        $Article->Comment = new Model(array('name' => 'Comment', 'datasource' => 'test'));

        $Article->hasMany('Comment', array(
          'dependent' => true,
        ));
        $params = array('conditions' => array('article_id' => $articleId));
      
        $count = $Article->Comment->find('count', $params);
        $this->assertEquals(3, $count);
        $this->assertTrue($Article->delete($articleId, true));
        $this->assertEquals(0, $Article->Comment->find('count', $params));
    }

    public function testDeleteHABTM()
    {
        $Article = new Model(array('name' => 'Article', 'datasource' => 'test'));
        $Article->Tag = new Model(array('name' => 'Tag', 'datasource' => 'test'));
        $Article->hasAndBelongsToMany('Tag');
        $data = $Article->new(array(
          'id' => 3,
          'tags' => array(
            array('title' => 'DeleteHABTM'),
          ),
        ), ['associated'=>['Tag']]);
        $params = array(
         'conditions' => array('article_id' => 3),
         'fields' => array('tag_id'),
       );

        $this->assertEquals(0, $Article->ArticlesTag->find('count', $params)); // Checks
        $this->assertTrue($Article->save($data));

        $this->assertEquals(1, $Article->ArticlesTag->find('count', $params)); // Checks
        $this->assertTrue($Article->delete(3, true));
        //   $this->assertEquals(0,$Article->ArticlesTag->find('count',$params)); // Checks
    }

    public function testpatch()
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
        $Article->Author = new Model(array('name' => 'User','alias'=>'Author', 'datasource' => 'test'));
        $Article->Tag = new Model(array('name' => 'Tag', 'datasource' => 'test'));
        $Article->belongsTo('Author');
        $Article->hasMany('Tag');
        $Entity = $Article->new($data, ['associated'=>['Author','Tag']]);

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
        $patchedEntity = $Article->patch($Entity, $requestData, ['associated'=>['Author','Tag']]);
        $this->assertEquals('New Article Name', $patchedEntity->title);
        $this->assertEquals('Claire', $patchedEntity->author->name);
        $this->assertEquals('published', $patchedEntity->tags[0]->tag);
        $this->assertEquals('top ten', $patchedEntity->tags[1]->tag);
    }

    public function testLoadBehavior()
    {
        $Article = new Model(array('name' => 'Article', 'datasource' => 'test'));
        $Article->loadBehavior('BehaviorTester', ['className' => 'Origin\Test\Model\BehaviorTesterBehavior']);
        $this->assertObjectHasAttribute('BehaviorTester', $Article);
        $this->assertInstanceOf('Origin\Test\Model\BehaviorTesterBehavior', $Article->BehaviorTester);
    }

    public function testLoadModel()
    {
        $Article = new Model(['name' => 'Article', 'datasource' => 'test']);
        ModelRegistry::set('Author', new Model(['name' => 'Author', 'datasource' => 'test']));
        $this->assertInstanceOf(Model::class, $Article->loadModel('Author'));
        $this->expectException(MissingModelException::class);
        $Article->loadModel('Bananana');
    }

    public function testValidates()
    {
        $Article = new Model(['name' => 'Article', 'datasource' => 'test']);
        $Article->validate('title', 'notBlank');
        $entity = $Article->new(['title'=>null]);
        $this->assertFalse($Article->validates($entity));
        $entity = $Article->new(['title'=>'foo']);
        $this->assertTrue($Article->validates($entity));
    }

    public function testIsset()
    {
        $Article = new Model(['name' => 'Article', 'datasource' => 'test']);
        $this->assertFalse(isset($Article->Foo));
    }
}
