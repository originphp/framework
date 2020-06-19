<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
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

use ArrayObject;
use Origin\Model\Entity;

use Origin\Model\Collection;
use Origin\Security\Security;
use Origin\Model\ModelRegistry;
use Origin\Core\Exception\Exception;
use Origin\Model\Model as BaseModel;
use Origin\TestSuite\OriginTestCase;

use Origin\Model\Exception\NotFoundException;
use Origin\Model\Exception\DatasourceException;

use Origin\Model\Exception\MissingModelException;
use Origin\Core\Exception\InvalidArgumentException;

class Model extends BaseModel
{
    public function connectionName(): string
    {
        return $this->connection;
    }
}

/**
 * Used By Mocks
 */
class Article extends Model
{
    protected $connection = 'test';

    /**
     * This will used later
     *
     * @return void
     */
    public function initCallbacks()
    {
        $this->beforeFind('beforeFindCallback');
        $this->afterFind('afterFindCallback');
        $this->beforeCreate('beforeCreateCallback');
        $this->afterCreate('afterCreateCallback');
        $this->beforeValidate('beforeValidateCallback');
        $this->afterValidate('afterValidateCallback');
        $this->beforeUpdate('beforeUpdateCallback');
        $this->afterUpdate('afterUpdateCallback');

        $this->beforeSave('beforeSaveCallback');
        $this->afterSave('afterSaveCallback');

        $this->beforeDelete('beforeDeleteCallback');
        $this->afterDelete('afterDeleteCallback');

        $this->afterRollback('afterRollbackCallback');
        $this->afterCommit('afterCommitCallback');
    }

    /**
     * Before find callback must return a bool. Returning false will stop the find operation.
     *
     * @param ArrayObject $options
     * @return boolean
     */
    public function beforeFindCallback(ArrayObject $options): bool
    {
        return true;
    }

    /**
     * After find callback
     *
     * @param Collection $results
     * @param ArrayObject $options
     * @return void
     */
    public function afterFindCallback(Collection $results, ArrayObject $options): void
    {
    }

    /**
     * Before Validation takes places, must return true to continue
     *
     * @param \Origin\Model\Entity $entity
     * @param ArrayObject $options
     * @return boolean
     */
    public function beforeValidateCallback(Entity $entity, ArrayObject $options): bool
    {
        return true;
    }

    /**
     * After Validation callback
     *
     * @param \Origin\Model\Entity $entity
     * @param ArrayObject $options
     * @return void
     */
    public function afterValidateCallback(Entity $entity, ArrayObject $options): void
    {
    }

    /**
     * Before save callback
     *
     * @param \Origin\Model\Entity $entity
     * @param ArrayObject $options
     * @return boolean
     */
    public function beforeSaveCallback(Entity $entity, ArrayObject $options): bool
    {
        return true;
    }

    /**
     * Before create callback
     *
     * @param \Origin\Model\Entity $entity
     * @param ArrayObject $options
     * @return boolean
     */
    public function beforeCreateCallback(Entity $entity, ArrayObject $options): bool
    {
        return true;
    }

    /**
     * Before update callback
     *
     * @param \Origin\Model\Entity $entity
     * @param ArrayObject $options
     * @return boolean
     */
    public function beforeUpdateCallback(Entity $entity, ArrayObject $options): bool
    {
        return true;
    }

    /**
    * After create callback
     *
     * @param \Origin\Model\Entity $entity
     * @param ArrayObject $options
     * @return void
     */
    public function afterCreateCallback(Entity $entity, ArrayObject $options): void
    {
    }

    /**
    * After update callback
     *
     * @param \Origin\Model\Entity $entity
     * @param ArrayObject $options
     * @return void
     */
    public function afterUpdateCallback(Entity $entity, ArrayObject $options): void
    {
    }

    /**
     * After save callback
     *
     * @param \Origin\Model\Entity $entity
     * @param ArrayObject $options
     * @return void
     */
    public function afterSaveCallback(Entity $entity, ArrayObject $options): void
    {
    }

    /**
     * Before delete, must return true to continue
     *
     * @param \Origin\Model\Entity $entity
     * @param ArrayObject $options
     * @return bool
     */
    public function beforeDeleteCallback(Entity $entity, ArrayObject $options): bool
    {
        return true;
    }

    /**
     * After delete callback
     *
     * @param \Origin\Model\Entity $entity
     * @param boolean $sucess wether or not it deleted the record
     * @return void
     */
    public function afterDeleteCallback(Entity $entity, ArrayObject $options): void
    {
    }

    /**
    * After commit callback
    *
    * @param \Origin\Model\Entity $entity
    * @param ArrayObject $options
    * @return bool
    */
    public function afterCommitCallback(Entity $entity, ArrayObject $options): void
    {
    }

    /**
    * After rollback callback
    *
    * @param \Origin\Model\Entity $entity
    * @param ArrayObject $options
    * @return void
    */
    public function afterRollbackCallback(Entity $entity, ArrayObject $options): void
    {
    }

    /**
     * This is a callback is called when an exception is caught
     *
     * @param \Exception $exception
     * @return void
     */
    public function onError(\Exception $exception): void
    {
    }

    public function callbacks(string $callback)
    {
        return $this->registeredCallbacks($callback);
    }
}

class ModelTest extends OriginTestCase
{
    protected $fixtures = [
        'Framework.Article',
        'Framework.ArticlesTag',
        'Framework.Author',
        'Framework.Book',
        'Framework.Comment',
        'Framework.Tag',
        'Framework.Address',
        'Framework.Thread', // Use this to test increment/decrement
    ];

    /**
     * Model
     *
     * @var \Origin\Model\Model;
     */
    protected $Article = null;

    protected function setUp(): void
    {
        $this->Article = new Model([
            'name' => 'Article',
            'connection' => 'test',
        ]);
        $this->Author = new Model([
            'name' => 'Author',
            'connection' => 'test',
        ]);
        $this->Book = new Model([
            'name' => 'Book',
            'connection' => 'test',
        ]);
        $this->Comment = new Model([
            'name' => 'Comment',
            'connection' => 'test',
        ]);

        $this->Tag = new Model([
            'name' => 'Tag',
            'connection' => 'test',
        ]);

        $this->Address = new Model([
            'name' => 'Address',
            'connection' => 'test',
        ]);
    
        ModelRegistry::set('Article', $this->Article);
        ModelRegistry::set('Author', $this->Author);
        ModelRegistry::set('Book', $this->Book);
        ModelRegistry::set('Comment', $this->Comment);
        ModelRegistry::set('Tag', $this->Tag);
        ModelRegistry::set('Address', $this->Address);
    }

    public function testRegisterCallbackBeforeCreate()
    {
        $Article = new Article();
        $Article->beforeCreate('beforeCreateCallback');

        $expected = ['beforeCreateCallback' => ['on' => 'create']];
        $this->assertEquals($expected, $Article->callbacks('beforeCreate'));
    }

    public function testRegisterCallbackBeforeValidate()
    {
        $Article = new Article();
        $Article->beforeValidate('beforeValidateCallback');

        $expected = ['beforeValidateCallback' => ['on' => ['create','update']]];
        $this->assertEquals($expected, $Article->callbacks('beforeValidate'));
    }

    public function testRegisterCallbackBeforeUpdate()
    {
        $Article = new Article();
        $Article->beforeUpdate('beforeUpdateCallback');

        $expected = ['beforeUpdateCallback' => ['on' => 'update']];
        $this->assertEquals($expected, $Article->callbacks('beforeUpdate'));
    }

    public function testRegisterCallbackBeforeSave()
    {
        $Article = new Article();
        $Article->beforeSave('beforeSaveCallback');

        $expected = ['beforeSaveCallback' => ['on' => ['create','update']]];
        $this->assertEquals($expected, $Article->callbacks('beforeSave'));
    }

    public function testRegisterCallbackBeforeDelete()
    {
        $Article = new Article();
        $Article->beforeDelete('beforeDeleteCallback');

        $expected = ['beforeDeleteCallback' => ['on' => 'delete']];
        $this->assertEquals($expected, $Article->callbacks('beforeDelete'));
    }

    public function testRegisterCallbackAfterCreate()
    {
        $Article = new Article();
        $Article->afterCreate('afterCreateCallback');

        $expected = ['afterCreateCallback' => ['on' => 'create']];
        $this->assertEquals($expected, $Article->callbacks('afterCreate'));
    }

    public function testRegisterCallbackAfterValidate()
    {
        $Article = new Article();
        $Article->afterValidate('afterValidateCallback');

        $expected = ['afterValidateCallback' => ['on' => ['create','update']]];
        $this->assertEquals($expected, $Article->callbacks('afterValidate'));
    }

    public function testRegisterCallbackAfterUpdate()
    {
        $Article = new Article();
        $Article->afterUpdate('afterUpdateCallback');

        $expected = ['afterUpdateCallback' => ['on' => 'update']];
        $this->assertEquals($expected, $Article->callbacks('afterUpdate'));
    }

    public function testRegisterCallbackAfterSave()
    {
        $Article = new Article();
        $Article->afterSave('afterSaveCallback');

        $expected = ['afterSaveCallback' => ['on' => ['create','update']]];
        $this->assertEquals($expected, $Article->callbacks('afterSave'));
    }

    public function testRegisterCallbackAfterDelete()
    {
        $Article = new Article();
        $Article->afterDelete('afterDeleteCallback');

        $expected = ['afterDeleteCallback' => ['on' => 'delete']];
        $this->assertEquals($expected, $Article->callbacks('afterDelete'));
    }

    public function testRegisterCallbackAfterRollback()
    {
        $Article = new Article();
        $Article->afterRollback('afterRollbackCallback');

        $expected = ['afterRollbackCallback' => ['on' => ['create','update','delete']]];
        $this->assertEquals($expected, $Article->callbacks('afterRollback'));
    }

    public function testRegisterCallbackAfterCommit()
    {
        $Article = new Article();
        $Article->afterCommit('afterCommitCallback');

        $expected = ['afterCommitCallback' => ['on' => ['create','update','delete']]];
        $this->assertEquals($expected, $Article->callbacks('afterCommit'));
    }

    public function testSaveExceptionRollback()
    {
        if ($this->Article->connection()->engine() === 'sqlite') {
            $this->markTestSkipped('Skipping as this does not generate exception in mysqli');
        }

        $this->expectException(DatasourceException::class);
        $data = [
            'title' => str_repeat('x', 256),
            'author_id' => 1234,
            'body' => 'article body goes here',
        ];

        $stub = $this->getMockForModel('Article', [
            'begin','rollback',
        ], ['className' => Article::class]);

        $stub->expects($this->once())
            ->method('begin');

        $stub->expects($this->once())
            ->method('rollback');

        $article = $this->Article->new($data);
        $this->assertFalse($stub->save($article));
    }

    public function testDeleteExceptionRollback()
    {
        $this->expectException(DatasourceException::class);
        //Invalid text representation: 7 ERROR:  invalid input syntax for integer: "ab78e847-6ea9-4f88-9b10-8c29f2993616"
        //yntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near '-not-e
        $data = [
            'id' => Security::uuid()
        ];

        $stub = $this->getMockForModel('Article', [
            'begin','rollback','exists'
        ], ['className' => Article::class,'table' => 'throw-an-exception']);

        $stub->expects($this->once())
            ->method('begin');

        $stub->expects($this->once())
            ->method('exists')
            ->willReturn(true);

        $stub->expects($this->once())
            ->method('rollback');

        $article = $this->Article->new($data);
        $stub->delete($article);
    }

    /**
     * @todo Eventually this all needs to be refactored due to feature creep
     *
     * @return void
     */
    public function testClearingEmptyRelatedResults()
    {
        $this->Article->belongsTo('Author');
        $this->Article->Author->hasOne('Address');
        $result = $this->Article->get(1000, [
            'associated' => ['Author'],
        ]);

        $this->assertNotEmpty($result);
        $this->assertNotEmpty($result->author);

        $this->Article->updateColumn(1000, 'author_id', 12345678); // invalid id
        $result = $this->Article->get(1000, [
            'associated' => ['Author'],
        ]);

        $this->assertNotEmpty($result);
        $this->assertNull($result->author);

        $result = $this->Article->Author->get(1000, [
            'associated' => ['Address'],
        ]);
        $this->assertNotEmpty($result);
        $this->assertNotEmpty($result->address);

        $this->Article->Author->Address->updateColumn(1002, 'author_id', 12345678); // invalid id
        $result = $this->Article->Author->get(1000, [
            'associated' => ['Address'],
        ]);

        $this->assertNotEmpty($result);
        $this->assertNull($result->address);
    }

    public function testIncrementDecrement()
    {
        $Thread = new Model([
            'name' => 'Thread',
            'connection' => 'test',
        ]);

        $this->assertTrue($Thread->increment('views', 1000));
        $this->assertTrue($Thread->increment('views', 1000));
        $thread = $Thread->get(1000);
        $this->assertEquals(2, $thread->views);

        $this->assertTrue($Thread->decrement('views', 1000));
        $this->assertTrue($Thread->decrement('views', 1000));
        $thread = $Thread->get(1000);
        $this->assertEquals(0, $thread->views);
    }

    public function testConstruct()
    {
        $Model = new Model();
        $this->assertEquals('Model', $Model->name());
        $this->assertEquals('Model', $Model->alias());
        $this->assertEquals('models', $Model->table());
        $this->assertEquals('default', $Model->connectionName());

        $Post = new Model(['name' => 'Post']);
        $this->assertEquals('Post', $Post->name());
        $this->assertEquals('Post', $Post->alias());
        $this->assertEquals('posts', $Post->table());
        $this->assertEquals('default', $Model->connectionName());

        $Post = new Model(['name' => 'Post', 'alias' => 'BlogPost', 'connection' => 'test']);
        $this->assertEquals('Post', $Post->name());
        $this->assertEquals('BlogPost', $Post->alias());
        $this->assertEquals('posts', $Post->table());
        $this->assertEquals('test', $Post->connectionName());
    }

    public function testFields()
    {
        $fields = $this->Article->fields();

        $expected = [
            'articles.id',
            'articles.author_id',
            'articles.title',
            'articles.body',
            'articles.created',
            'articles.modified',
        ];
        $this->assertEquals($expected, $fields);

        $fields = $this->Article->fields(false);

        $expected = [
            'id',
            'author_id',
            'title',
            'body',
            'created',
            'modified',
        ];
        $this->assertEquals($expected, $fields);
    }

    public function testSchema()
    {
        $ds = $this->Article->connection();
        
        $schema = $this->Article->schema();

        $this->assertIsArray($schema);

        $this->assertArrayHasKey('columns', $schema);
        $this->assertNotEmpty($schema['columns']);

        $this->assertArrayHasKey('constraints', $schema);
        $this->assertNotEmpty($schema['constraints']);

        $this->assertArrayHasKey('indexes', $schema);
        $this->assertEmpty($schema['indexes']); // no index set
        
        $this->assertArrayHasKey('id', $schema['columns']);
        
        if ($ds->engine() === 'mysql') {
            $this->assertArrayHasKey('options', $schema);
            $this->assertNotEmpty($schema['options']);
        }
        $schema = $this->Article->schema('id');

        $expected = [
            'type' => 'integer',
            'limit' => 11,
            'unsigned' => false,
            'null' => false,
            'default' => null,
            'autoIncrement' => true,
        ];

        if ($ds->engine() === 'pgsql') {
            $expected = [
                'type' => 'integer',
                'limit' => 32,
                'null' => false,
                'default' => null,
                'autoIncrement' => true,
            ];
        } elseif ($ds->engine() === 'sqlite') {
            $expected = [
                'type' => 'integer',
                'limit' => null,
                'unsigned' => false,
                'null' => false,
                'default' => null,
                'autoIncrement' => true,
            ];
        }
        
        $this->assertEquals($expected, $schema);
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

        $options = ['constraints' => ['primary' => ['type' => 'primary', 'column' => 'not_id']]];
        $ds = $this->Article->connection();
        $statements = $ds->adapter()->createTableSql('foos', [
            'not_id' => ['type' => 'integer','autoIncrement' => true],
            'undetectable' => 'string'
        ], $options);
      
        foreach ($statements as $statement) {
            $ds->execute($statement);
        }
        
        $dummy = new Model(['name' => 'Foo','connection' => 'test']);
  
        $this->expectException(Exception::class);
        $display = $dummy->displayField;
        $ds->execute('DROP TABLE foos');
    }

    public function testDisplayFieldUndo()
    {
        $ds = $this->Article->connection();
        $this->assertTrue($ds->execute('DROP TABLE foos'));
    }

    public function testMagicHasOneDefault()
    {
        $Post = new Model(['name' => 'Post']);
        $relationship = (object) $Post->hasOne('Comment');

        $this->assertEquals('Comment', $relationship->className);

        $this->assertEquals('post_id', $relationship->foreignKey);
        $expected = ['posts.id = comments.post_id'];
        $this->assertEquals($expected, $relationship->conditions);
        $this->assertNull($relationship->fields);
        $this->assertFalse($relationship->dependent);
    }

    public function testMagicHasOneAlias()
    {
        $User = new Model(['name' => 'User']);
        $relationship = (object) $User->hasOne('Profile', ['className' => 'UserProfile']);
        $this->assertEquals('user_id', $relationship->foreignKey);
        $expected = ['users.id = profiles.user_id'];
        $this->assertEquals($expected, $relationship->conditions);
    }

    public function testMagicHasOneMerge()
    {
        $Post = new Model(['name' => 'Post']);
        $hasOneConfig = [
            'className' => 'FunkyComments',
            'foreignKey' => 'funky_post_id',
            'conditions' => [
                '1 == 1',
            ],
            'fields' => ['id', 'description'],
            'dependent' => true,
        ];

        // Test Merge went okay
        $relationship = $Post->hasOne('Comment', $hasOneConfig);
        $hasOneConfig['conditions'] = [
            'posts.id = comments.funky_post_id',
            '1 == 1',
        ];

        $this->assertEquals($hasOneConfig, $relationship);
    }

    public function testMagicBelongsToDefault()
    {
        // Test Default
        $Post = new Model(['name' => 'Post']);
        $relationship = (object) $Post->belongsTo('User');

        $this->assertEquals('User', $relationship->className);

        $this->assertEquals('user_id', $relationship->foreignKey);
        $expected = ['posts.user_id = users.id'];
        $this->assertEquals($expected, $relationship->conditions);
        $this->assertNull($relationship->fields);
        $this->assertEquals('LEFT', $relationship->type);
    }

    public function testMagicBelongsToAlias()
    {
        // Test Alias Stuff
        $Post = new Model(['name' => 'Post']);
        $relationship = (object) $Post->belongsTo('Owner', ['className' => 'User']);

        $this->assertEquals('user_id', $relationship->foreignKey);
        $expected = ['posts.user_id = owners.id'];
        $this->assertEquals($expected, $relationship->conditions);
    }

    public function testMagicBelongsToMerge()
    {
        // Test merge
        $Post = new Model(['name' => 'Post']);
        $belongsToConfig = [
            'alias' => 'Owner',
            'className' => 'User',
            'foreignKey' => 'owner_id',
            'conditions' => [
                '1 == 1',
            ],
            'fields' => ['id', 'name'],
            'type' => 'INNER',
        ];

        // Test Merge went okay
        $relationship = $Post->belongsTo('User', $belongsToConfig);

        $belongsToConfig['conditions'] = [
            'posts.owner_id = users.id',
            '1 == 1',
        ];

        $this->assertEquals($belongsToConfig, $relationship);
    }

    public function testMagicHasManyDefault()
    {
        // Test Default
        $Post = new Model(['name' => 'Post']);
        $relationship = (object) $Post->hasMany('Comment');

        $this->assertEquals('Comment', $relationship->className);

        $this->assertEquals('post_id', $relationship->foreignKey);
        $this->assertNull($relationship->fields);
        $this->assertNull($relationship->order);
        $this->assertFalse($relationship->dependent);
    }

    public function testMagicHasManyAlias()
    {
        $Post = new Model(['name' => 'Post']);
        $relationship = (object) $Post->hasMany('Comment', ['className' => 'VisitorComment']);
        $this->assertEquals('post_id', $relationship->foreignKey);
    }

    public function testMagicHasManyMerge()
    {
        $Post = new Model(['name' => 'Post']);
        $hasManyConfig = [
            'alias' => 'Owner',
            'className' => 'User',
            'foreignKey' => 'owner_id',
            'conditions' => [
                'posts.id = user_comments.post_id',
            ],
            'fields' => ['id', 'title'],
            'order' => ['created ASC'],
            'dependent' => true,
            'limit' => 10,
            'offset' => 5,
        ];

        // Test Merge went okay
        $relationship = $Post->hasMany('UserComment', $hasManyConfig);
        $this->assertEquals($hasManyConfig, $relationship);
    }

    public function testMagicHasAndBelongsToMany()
    {
        $Candidate = new Model(['name' => 'Job']);
        $relationship = $Candidate->hasAndBelongsToMany('Candidate');
        $expected = [
            'className' => 'Candidate',
            'joinTable' => 'candidates_jobs',
            'foreignKey' => 'job_id',
            'associationForeignKey' => 'candidate_id',
            'conditions' => ['candidates_jobs.candidate_id = candidates.id'],
            'fields' => null,
            'order' => null,
            'dependent' => null,
            'limit' => null,
            'offset' => null,
            'with' => 'CandidatesJob',
            'mode' => 'replace',
        ];
        $this->assertEquals($expected, $relationship);
        
        // Test Merging
        $relationship = $Candidate->hasAndBelongsToMany('Candidate', ['conditions' => ['Candidate.active' => true]]);
        $this->assertEquals('candidates_jobs.candidate_id = candidates.id', $relationship['conditions'][0]);
        $this->assertEquals(true, $relationship['conditions']['Candidate.active']);
    }

    public function testRelationsAgain()
    {
        $User = new Model(['name' => 'User']);
        $User->hasOne('Profile');
        $this->assertEquals('user_id', $User->association('hasOne')['Profile']['foreignKey']);

        $Profile = new Model(['name' => 'Profile']);
        $Profile->belongsTo('User');
        $this->assertEquals('user_id', $Profile->association('belongsTo')['User']['foreignKey']);

        $User = new Model(['name' => 'User']);
        $User->hasMany('Comment');
        $this->assertEquals('user_id', $User->association('hasMany')['Comment']['foreignKey']);

        $Ingredient = new Model(['name' => 'Ingredient']);
        $Ingredient->hasAndBelongsToMany('Recipe');
        $this->assertEquals(
            'ingredient_id',
            $Ingredient->association('hasAndBelongsToMany')['Recipe']['foreignKey']
        );
        $this->assertEquals(
            'recipe_id',
            $Ingredient->association('hasAndBelongsToMany')['Recipe']['associationForeignKey']
        );
    }

    public function testFindFirst()
    {
        $result = $this->Article->find('first');
        $this->assertInstanceOf(Entity::class, $result);

        $result = $this->Article->first();
        $this->assertInstanceOf(Entity::class, $result);
        
        $result = $this->Article->find('first', ['conditions' => ['id' => 123456789]]);
        $this->assertNull($result);

        $result = $this->Article->first(['conditions' => ['id' => 123456789]]);
        $this->assertNull($result);
    }

    public function testFindBy()
    {
        $result = $this->Article->findBy(['id' => 1001]);
        $this->assertEquals(1001, $result->id);
    }

    public function testFindAllBy()
    {
        $result = $this->Article->findAllBy(['id' => 1001]);
        $this->assertEquals(1001, $result[0]->id);
    }

    public function testFindAll()
    {
        $result = $this->Article->find('all');
        $this->assertInstanceOf(Collection::class, $result);

        $result = $this->Article->all();
        $this->assertInstanceOf(Collection::class, $result);
        
        $result = $this->Article->find('all', ['conditions' => ['id' => 123456789]]);
        $this->assertTrue(is_array($result));

        $result = $this->Article->all(['conditions' => ['id' => 123456789]]);
        $this->assertTrue(is_array($result));
    }
    public function testFindConditions()
    {
        $result = $this->Article->find('first', ['conditions' => ['id' => 1001]]);
        $this->assertEquals(1001, $result->id);

        $result = $this->Article->find('first', ['conditions' => ['articles.id' => 1001]]);
        $this->assertEquals(1001, $result->id);
    }

    public function testFindOrder()
    {
        $result = $this->Article->find('first', ['order' => 'created DESC']);
        $this->assertEquals(1002, $result->id);

        $result = $this->Article->find('first', ['order' => ['created ASC']]);
        $this->assertEquals(1000, $result->id);
    }

    public function testFindFields()
    {
        $result = $this->Article->find('first', ['fields' => ['id','title']])->toArray();
        $this->assertEquals(['id','title'], array_keys($result));

        $options = ['fields' => ['DISTINCT (author_id)']];
        $result = $this->Article->find('all', $options);
        $this->assertEquals(2, count($result));

        $options = ['fields' => ['DISTINCT (author_id),title']];
        $result = $this->Article->find('all', $options);
        $this->assertEquals(3, count($result));

        $result = $this->Article->find('all', [
            'fields' => ['COUNT(*) as total','author_id'],
            'group' => 'author_id','order' => 'author_id ASC', //mysql 8/5 return in different order
        ]);

        $this->assertEquals(1000, $result[0]->author_id);
        $this->assertEquals(2, $result[0]->total);
        $this->assertEquals(1001, $result[1]->author_id);
        $this->assertEquals(1, $result[1]->total);
        /**
         * Virtual fields not finalized yet but this is just to test that it is working
         * posts should be post since with virtual field it can be only one
         */
        $result = $this->Article->find('first', ['fields' => ['id','title as posts__title']]);
        $this->assertEquals('Article #1', $result->post->title);
    }

    public function testFindLimit()
    {
        $result = $this->Article->find('all', ['limit' => 2]);
        $this->assertEquals(2, count($result));
    }

    public function testFindJoin()
    {
        $conditions = [
            'conditions' => ['id' => 1000],
            'fields' => ['articles.id','articles.title','authors.name'],
            'joins' => [],
        ];
        $conditions['joins'][] = [
            'table' => 'authors',
            'alias' => 'authors',
            'type' => 'LEFT', // this is defualt,
            'conditions' => [
                'authors.id = articles.author_id',
            ],
        ];
       
        $result = $this->Article->find('first', $conditions);
       
        $this->assertEquals('Author #2', $result->author->name);
    }

    public function testFindCount()
    {
        $result = $this->Article->find('count', ['fields' => ['really_does_not_matter']]);
        $this->assertEquals(3, $result);

        $result = $this->Article->find('count', ['conditions' => ['id' => 123456789]]);
        $this->assertEquals(0, $result);

        $result = $this->Article->find('count', ['group' => 'author_id','order' => 'author_id ASC']);
        $expected = [
            ['count' => 2, 'author_id' => 1000],
            ['count' => 1, 'author_id' => 1001]
        ];
 
        $this->assertEquals($expected, $result);
    }

    public function testAggregates()
    {
        $this->assertEquals(3, $this->Article->count());
        $this->assertEquals(1001.0000000000000000, $this->Article->average('id'));
        $this->assertEquals(3003, $this->Article->sum('id'));
        $this->assertEquals(1000, $this->Article->minimum('id'));
        $this->assertEquals(1002, $this->Article->maximum('id'));

        $result = $this->Article->count('all', ['group' => 'author_id','order' => 'author_id ASC']);
        $expected = [
            ['count' => 2, 'author_id' => 1000],
            ['count' => 1, 'author_id' => 1001]
        ];
        $this->assertEquals($expected, $result);
    }

    public function testFindList()
    {
        $list = $this->Article->find('list', ['fields' => ['id']]); // ['a','b','c']
        $this->assertEquals([1000,1001,1002], $list);

        $list = $this->Article->find('list', ['fields' => ['id','title']]); // ['a'=>'b']
        $this->assertEquals([1000 => 'Article #1',1001 => 'Article #2',1002 => 'Article #3'], $list);

        $list = $this->Article->find('list', ['fields' => ['id','title','author_id']]); // ['c'=>['a'=>'b']
        $expected = [
            1001 => [1000 => 'Article #1'],
            1000 => [1001 => 'Article #2',1002 => 'Article #3'],
        ];
        $this->assertEquals($expected, $list);
    }

    /**
     * @depends testFindList
     */
    public function testList()
    {
        $list = $this->Article->list(['fields' => ['id']]); // ['a','b','c']
        $this->assertEquals([1000,1001,1002], $list);
    }

    public function testFindCallbacks()
    {
        # Stub Model
        $stub = $this->getMockForModel('Article', [
            'beforeFindCallback','afterFindCallback',
        ], ['className' => Article::class]);
        $stub->initCallbacks();
        $stub->expects($this->once())
            ->method('beforeFindCallback')
            ->willReturn(true);

        $stub->expects($this->once())
            ->method('afterFindCallback');

        $stub->find('first');
    }

    public function testFindCallbacksHalt()
    {
        //Article::class
        $stub = $this->getMockForModel('Article', [
            'beforeFindCallback','afterFindCallback',
        ], ['className' => Article::class]);
        $stub->initCallbacks();
        $stub->expects($this->once())
            ->method('beforeFindCallback')
            ->willReturn(false);

        $stub->expects($this->never())
            ->method('afterFindCallback');

        $stub->find('first');
    }

    public function testFindCallbacksDisabled()
    {
        $stub = $this->getMockForModel('Article', [
            'beforeFindCallback','afterFindCallback',
        ], ['className' => Article::class]);
        $stub->initCallbacks();
        $stub->expects($this->never())
            ->method('beforeFindCallback');

        $stub->expects($this->never())
            ->method('afterFindCallback');

        $stub->find('first', ['callbacks' => false]);
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
            'conditions' => ['id' => 1000],
            'associated' => ['Author'],
        ]);

        $this->assertEquals(1001, $result->author_id);
        $this->assertEquals(1001, $result->author->id);
        $this->assertEquals('Author #2', $result->author->name);
        $this->assertTrue($result->author->has('created'));

        $result = $this->Article->find('first', [
            'associated' => ['Author' => ['fields' => ['id','name']]],
        ]);
        $this->assertFalse($result->author->has('created'));
       
        $result = $this->Article->find('first', [
            'conditions' => ['id' => 1001],
            'associated' => ['Author' => ['associated' => ['Address']]],
        ]);
    
        $this->assertEquals(1000, $result->author_id);
        $this->assertEquals(1000, $result->author->id);
        $this->assertEquals(1000, $result->author->address->author_id);

        // Article id, has author  1001 and this author has no address
        $this->assertTrue($result->author->address->has('created'));
        $result = $this->Article->find('first', [
            'conditions' => ['id' => 1001],
            'associated' => ['Author' => ['associated' => ['Address' => ['fields' => ['id','author_id','description']]]]],
        ]);
        $this->assertFalse($result->author->address->has('created'));

        $result = $this->Article->Author->find('first', [
            'associated' => ['Address' => ['associated' => 'Author']],
            'conditions' => ['id' => 1000], // author id
        ]);

        $this->assertEquals(1000, $result->id);
        $this->assertEquals(1000, $result->address->author_id);
        $this->assertEquals(1000, $result->address->author->id);

        $this->expectException(InvalidArgumentException::class);
        $this->Article->find('first', ['associated' => ['Foo']]);
    }

    public function testFindAssociatedWithPrefix()
    {
        $this->Article->belongsTo('Author');
        $result = $this->Article->find('first', [
            'associated' => ['Author' => ['fields' => ['authors.id','authors.name as author_name']]],
        ]);
        
        $this->assertEquals(1001, $result->author->id);
        $this->assertEquals('Author #2', $result->author_name);
    }

    public function testFindAssociationFields()
    {
        $this->Article->belongsTo('Author');
        $this->Article->hasMany('Comment');

        $result = $this->Article->find('first', [
            'associated' => ['Author','Comment']
        ]);

        $this->assertArrayHasKey('created', $result->comments[0]);
        $this->assertArrayHasKey('created', $result->author);

        $this->Article->belongsTo('Author', [
            'fields' => ['authors.id','authors.name','authors.description']
        ]);
        $this->Article->hasMany('Comment', [
            'fields' => ['comments.id','comments.article_id','comments.description']
        ]);
       
        $result = $this->Article->find('first', [
            'associated' => ['Author','Comment']
        ]);
        $this->assertArrayNotHasKey('created', $result->comments[0]);
        $this->assertEquals('Comment #2', $result->comments[0]['description']);

        $this->assertArrayNotHasKey('created', $result->author);
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
        $result = $this->Article->query('SELECT title from articles WHERE id = :id', ['id' => 1002]);
        $this->assertEquals('Article #3', $result[0]['title']);

        $result = $this->Article->query('DELETE from articles WHERE id = :id', ['id' => 1000]);
        $this->assertTrue($result);
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
        $article = $this->Article->new(['title' => null]);
        $this->assertFalse($this->Article->validates($article));
        $article = $this->Article->new(['title' => 'Test']);
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
        $this->assertNotEmpty($this->Article->id());

        $this->assertTrue($article->created());
        $this->assertFalse($article->deleted());
   
        # # # READ # # #
        $result = $this->Article->get($article->id);
        $this->assertEquals('Testing CRUD', $article->title);

        # # # UPDATE # # #
        $requestData = ['title' => 'Testing Update in CRUD','description' => 'Lovely Jubely'];
        $article = $this->Article->patch($result, $requestData);
        $this->assertNotEmpty($article->modified());
        $this->assertTrue($this->Article->save($article));

        $this->assertFalse($article->created());
        $this->assertFalse($article->deleted());

        $result = $this->Article->get($article->id);
        $this->assertEquals('Testing Update in CRUD', $article->title);

        # # # DELETE # # #
        $article = $this->Article->patch($result, $requestData);
        $this->assertTrue($this->Article->delete($article));
        $this->assertFalse($this->Article->delete($article));
        $this->assertFalse($article->created());
        $this->assertTrue($article->deleted());
    }

    public function testUpdateColumn()
    {
        $this->Article->updateColumn(1000, 'title', 'foo');
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
            'rule' => 'notBlank',
            'required' => true,
            'on' => 'create']);
        
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
            'beforeValidateCallback','afterValidateCallback','beforeSaveCallback','afterSaveCallback','beforeCreateCallback','afterCreateCallback','afterCommitCallback','beforeUpdateCallback','afterUpdateCallback','afterRollbackCallback'
        ], ['className' => Article::class]);
        $stub->initCallbacks();
        $stub->expects($this->once())
            ->method('beforeValidateCallback')
            ->willReturn(true);

        $stub->expects($this->once())
            ->method('afterValidateCallback');

        $stub->expects($this->once())
            ->method('beforeSaveCallback')
            ->willReturn(true);

        $stub->expects($this->once())
            ->method('beforeCreateCallback')
            ->willReturn(true);

        $stub->expects($this->once())
            ->method('afterCreateCallback');

        $stub->expects($this->never())
            ->method('beforeUpdateCallback')
            ->willReturn(true);

        $stub->expects($this->never())
            ->method('afterUpdateCallback');

        $stub->expects($this->once())
            ->method('afterSaveCallback');

        $stub->expects($this->once())
            ->method('afterCommitCallback');

        $stub->expects($this->never())
            ->method('afterRollbackCallback');
     
        $article = $stub->new();
        $article->title = 'Callback Test';
        $article->author_id = 512;
        $article->body = 'Article body goes here.';

        $this->assertTrue($stub->save($article));
    }

    public function testSaveCallbacksUpdate()
    {
        $stub = $this->getMockForModel('Article', [
            'beforeValidateCallback','afterValidateCallback','beforeSaveCallback','afterSaveCallback','beforeCreateCallback','afterCreateCallback','afterCommitCallback','beforeUpdateCallback','afterUpdateCallback','afterRollbackCallback'
        ], ['className' => Article::class]);
        $stub->initCallbacks();

        $stub->expects($this->once())
            ->method('beforeValidateCallback')
            ->willReturn(true);

        $stub->expects($this->once())
            ->method('afterValidateCallback');

        $stub->expects($this->once())
            ->method('beforeSaveCallback')
            ->willReturn(true);

        $stub->expects($this->never())
            ->method('beforeCreateCallback')
            ->willReturn(true);

        $stub->expects($this->never())
            ->method('afterCreateCallback');

        $stub->expects($this->once())
            ->method('beforeUpdateCallback')
            ->willReturn(true);

        $stub->expects($this->once())
            ->method('afterUpdateCallback');

        $stub->expects($this->once())
            ->method('afterSaveCallback');

        $stub->expects($this->once())
            ->method('afterCommitCallback');

        $stub->expects($this->never())
            ->method('afterRollbackCallback');

        $Article = new Model(['name' => 'Article','connection' => 'test']);
        $article = $Article->find('first');
        $article->title = 'title has changed';
  
        $this->assertTrue($stub->save($article));
    }

    /**
     * @depends testCrud
     */
    public function testSaveCallbacksValidationFail()
    {
        $stub = $this->getMockForModel('Article', [
            'beforeValidateCallback','afterValidateCallback','beforeSaveCallback','afterSaveCallback',
        ], ['className' => Article::class]);
        $stub->initCallbacks();
        $stub->expects($this->once())
            ->method('beforeValidateCallback')
            ->willReturn(false);

        $stub->expects($this->never())
            ->method('afterValidateCallback');

        $stub->expects($this->never())
            ->method('beforeSaveCallback');

        $stub->expects($this->never())
            ->method('afterSaveCallback');

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
            'beforeValidateCallback','afterValidateCallback','beforeSaveCallback','afterSaveCallback','afterRollbackCallback','afterCommitCallback'
        ], ['className' => Article::class]);

        $stub->initCallbacks();
        $stub->expects($this->once())
            ->method('beforeValidateCallback')
            ->willReturn(true);

        $stub->expects($this->once())
            ->method('afterValidateCallback');

        $stub->expects($this->once())
            ->method('beforeSaveCallback')
            ->willReturn(false);

        $stub->expects($this->never())
            ->method('afterSaveCallback');

        $stub->expects($this->never())
            ->method('afterCommitCallback');

        $stub->expects($this->once())
            ->method('afterRollbackCallback');

        $article = $stub->new();
        $article->author_id = 1234;
        $article->title = 'Mocked method will return false';
        $article->body = 'Article body goes here.';

        $this->assertFalse($stub->save($article));
    }

    /**
     * @depends testCrud
     */
    public function testSaveCallbacksBeforeCreateReturnFalse()
    {
        $stub = $this->getMockForModel('Article', [
            'beforeValidateCallback','afterValidateCallback','beforeSaveCallback','afterSaveCallback','afterRollbackCallback','beforeCreateCallback','afterCreateCallback','beforeUpdateCallback','afterUpdateCallback'
        ], ['className' => Article::class]);
        $stub->initCallbacks();
        $stub->expects($this->once())
            ->method('beforeValidateCallback')
            ->willReturn(true);
  
        $stub->expects($this->once())
            ->method('afterValidateCallback');
  
        $stub->expects($this->once())
            ->method('beforeSaveCallback')
            ->willReturn(true);

        $stub->expects($this->once())
            ->method('beforeCreateCallback')
            ->willReturn(false);

        $stub->expects($this->never())
            ->method('afterCreateCallback');
    
        $stub->expects($this->never())
            ->method('beforeUpdateCallback');

        $stub->expects($this->never())
            ->method('afterUpdateCallback');
  
        $stub->expects($this->never())
            ->method('afterSaveCallback');
  
        $stub->expects($this->once())
            ->method('afterRollbackCallback');
  
        $article = $stub->new();
        $article->author_id = 1234;
        $article->title = 'Mocked method will return false';
        $article->body = 'Article body goes here.';
  
        $this->assertFalse($stub->save($article));
    }

    /**
     * @depends testCrud
     */
    public function testSaveCallbacksBeforeUpdateReturnFalse()
    {
        $stub = $this->getMockForModel('Article', [
            'beforeValidateCallback','afterValidateCallback','beforeSaveCallback','afterSaveCallback','afterRollbackCallback','beforeCreateCallback','afterCreateCallback','beforeUpdateCallback','afterUpdateCallback'
        ], ['className' => Article::class]);
        $stub->initCallbacks();
        $stub->expects($this->once())
            ->method('beforeValidateCallback')
            ->willReturn(true);
  
        $stub->expects($this->once())
            ->method('afterValidateCallback');
  
        $stub->expects($this->once())
            ->method('beforeSaveCallback')
            ->willReturn(true);

        $stub->expects($this->once())
            ->method('beforeUpdateCallback')
            ->willReturn(false);

        $stub->expects($this->never())
            ->method('afterUpdateCallback');
    
        $stub->expects($this->never())
            ->method('beforeCreateCallback');

        $stub->expects($this->never())
            ->method('afterCreateCallback');
  
        $stub->expects($this->never())
            ->method('afterSaveCallback');
  
        $stub->expects($this->once())
            ->method('afterRollbackCallback');
  
        $Article = new Model(['name' => 'Article','connection' => 'test']);
        $article = $Article->find('first');
        $article->title = 'title has changed';
      
        $this->assertFalse($stub->save($article));
    }

    /**
      * @depends testCrud
      */
    public function testSaveCallbacksDisabled()
    {
        $stub = $this->getMockForModel('Article', [
            'beforeValidateCallback','afterValidateCallback','beforeSaveCallback','afterSaveCallback',
        ], ['className' => Article::class]);
        $stub->initCallbacks();
        $stub->expects($this->never())
            ->method('beforeValidateCallback');

        $stub->expects($this->never())
            ->method('afterValidateCallback');

        $stub->expects($this->never())
            ->method('beforeSaveCallback');

        $stub->expects($this->never())
            ->method('afterSaveCallback');

        $article = $stub->new();
        $article->author_id = 1234;
        $article->title = 'Mocked method will return false';
        $article->body = 'Article body goes here.';

        $this->assertTrue($stub->save($article, ['callbacks' => false]));
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
                'rating' => 5,
            ],
            'comments' => [
                ['description' => 'Save Comment #1'],
                ['description' => 'Save Comment #2'],
            ],
        ];
        $article = $this->Article->new($data);
        $this->Article->save($article, ['associated' => false]);
        $this->assertNull($article->author->id);
        $this->assertNull($article->comments[0]->id);

        $this->Author->hasOne('Address');

        $data = [
            'name' => 'Hiro Nakamura',
            'location' => 'Japan',
            'rating' => 5,
            'address' => [
                'description' => 'Last seen in Tokyo',
            ],
        ];
       
        $author = $this->Author->new($data);
    
        $this->Author->save($author, ['associated' => false]);
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
                'rating' => 5,
            ],
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
                'description' => 'Last seen in Tokyo',
            ],
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
                ['description' => 'Save Comment #2'],
            ],
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
            'body' => 'Article body',
        ];
        $article = $this->Article->new($data);
        $this->assertTrue($this->Article->save($article));
     
        $data = [
            'id' => $article->id,
            'tags' => [
                ['id' => 1001],
                ['id' => 1002],
            ],
        ];
        $article = $this->Article->new($data);
        $this->assertTrue($this->Article->save($article));

        $article = $this->Article->get($article->id, ['associated' => ['Tag']]);
        $this->assertEquals(2, count($article->tags));

        // test non existant ids
        $data = [
            'id' => $article->id,
            'tags' => [
                ['id' => 1010101],
                ['id' => 1010102],
            ],
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
            'body' => 'Article body',
        ];
        $article = $this->Article->new($data);
        $this->assertTrue($this->Article->save($article));
     
        $data = [
            'id' => 1000, # Article ID
            'tags' => [
                ['title' => 'Tag #1'],
                ['title' => 'Featured'],
                ['title' => 'Featured Again'],
            ],
        ];
        $article = $this->Article->new($data);
     
        $this->assertTrue($this->Article->save($article));
        # Postgre returns different id numbers
        $this->assertNotEmpty($article->tags[0]->id);
        $this->assertNotEmpty($article->tags[1]->id);
        $this->assertNotEquals($article->tags[0]->id, $article->tags[1]->id);
        
        $article = $this->Article->get($article->id, ['associated' => ['Tag']]);
        $this->assertEquals(3, count($article->tags));
    }

    public function testSaveAssociatedHasAndBelongsToManyUnkown()
    {
        $this->Article->hasAndBelongsToMany('Tag');
        $this->Article->Tag->hasAndBelongsToMany('Tag');
        
        $data = [
            'id' => 1000,
            'tags' => [
                ['drink' => 'cola'],
            ],
        ];
        $article = $this->Article->new($data);
        $this->assertFalse($this->Article->save($article));
    }

    /**
      * @depends testCrud
      */
    public function testSaveAssociatedHasAndBelongsToManyAppend()
    {
        $this->Article->hasAndBelongsToMany('Tag', ['mode' => 'append']);
        $this->Article->Tag->hasAndBelongsToMany('Tag', ['mode' => 'append']);

        $article = $this->Article->get(1000, ['associated' => ['Tag']]);
     
        // test non existant ids
        $data = [
            'id' => $article->id,
            'tags' => [
                ['id' => 1000],
            ],
        ];
        $article = $this->Article->new($data);
        $this->assertTrue($this->Article->save($article));

        $article = $this->Article->get($article->id, ['associated' => ['Tag']]);
        $this->assertEquals(3, count($article->tags));

        $data = [
            'id' => 1001,
            'tags' => [
                ['title' => 'Tag #1'],
                ['title' => 'Tag #2'],
            ],
        ];
        $article = $this->Article->new($data);
        $this->assertTrue($this->Article->save($article));
        $article = $this->Article->get($article->id, ['associated' => ['Tag']]);
        $this->assertEquals(2, count($article->tags));
    }

    public function testNewEntities()
    {
        $data = [
            ['title' => 'Dummy Article #1','author_id' => 5432,'body' => '...'],
            ['title' => 'Dummy Article #2','author_id' => 6789,'body' => '...'],
        ];
        $articles = $this->Article->newEntities($data);
        $this->assertEquals('Dummy Article #1', $articles[0]->title);
        $this->assertEquals('Dummy Article #2', $articles[1]->title);
    }
    public function testSaveMany()
    {
        $data = [
            ['title' => 'Dummy Article #1','author_id' => 5432,'body' => '...'],
            ['title' => 'Dummy Article #2','author_id' => 6789,'body' => '...'],
            ['title' => 'Dummy Article #3','author_id' => 1212,'body' => '...'],
        ];
        $articles = $this->Article->newEntities($data);
        $this->assertTrue($this->Article->saveMany($articles));
        $this->assertNotEmpty($articles[0]->id);
        $this->assertNotEmpty($articles[1]->id);
        $this->assertNotEmpty($articles[2]->id);
        $this->assertNotEquals($articles[0]->id, $articles[1]->id);

        $stub = $this->getMockForModel('Article', ['save'], ['className' => Article::class]);

        $stub->expects($this->any())
            ->method('save')
            ->willReturn(false);
        $articles = $this->Article->newEntities($data);
        $this->assertFalse($stub->saveMany($articles));
    }

    public function testUpdateAll()
    {
        $result = $this->Article->updateAll(['title' => 'Updated Article'], ['id !=' => 1000]);
        $this->assertTrue($result);
        $count = $this->Article->find('count', ['conditions' => ['title' => 'Updated Article']]);
        $this->assertEquals(2, $count);
    }
    public function testDelete()
    {
        $this->Article->hasMany('Comment');
        $this->Article->hasAndBelongsToMany('Tag');

        $article = $this->Article->get(1000, ['associated' => ['Comment','Tag']]);
        $comments = count($article->comments);
        $tags = count($article->tags);
        # test deleteDepenent False
        $this->assertTrue($this->Article->delete($article));
    
        $this->assertEquals($comments, $this->Article->Comment->find('count', ['conditions' => ['article_id' => 1000]])); // did not delete
        $this->assertNotEquals($tags, $this->Article->ArticlesTag->find('count', ['conditions' => ['article_id' => 1000]])); // Delete always
     
       $this->Article->hasMany('Comment', ['dependent' => true]);
        $article = $this->Article->get(1002, ['associated' => ['Comment','Tag']]);
        $this->assertGreaterThan(0, count($article->comments));
        $this->assertTrue($this->Article->delete($article));
        $this->assertEquals(0, $this->Article->Comment->find('count', ['conditions' => ['article_id' => 1002]])); // did not delete
    }
 
    public function testDeleteNoCascade()
    {
        $this->Article->hasMany('Comment');
        $this->Article->hasAndBelongsToMany('Tag');

        $article = $this->Article->get(1000, ['associated' => ['Comment','Tag']]);
        $comments = count($article->comments);
        $tags = count($article->tags);
        $this->assertTrue($this->Article->delete($article, ['cascade' => false]));
        $this->assertEquals($comments, $this->Article->Comment->find('count', ['conditions' => ['article_id' => 1000]]));
    }

    public function testDeleteNoCallbacks()
    {
        $article = $this->Article->get(1000);
    
        # Stub Model
        $stub = $this->getMockForModel('Article', [
            'beforeDeleteCallback','afterDeleteCallback','afterCommitCallback',
        ], ['className' => Article::class]);
        $stub->initCallbacks();
        $stub->expects($this->never())
            ->method('beforeDeleteCallback')
            ->willReturn(true);

        $stub->expects($this->never())
            ->method('afterDeleteCallback');

        $stub->expects($this->never())
            ->method('afterCommitCallback');

        $this->assertTrue($stub->delete($article, ['callbacks' => false]));
        $this->assertEquals(0, $stub->find('count', ['conditions' => ['id' => 1000]]));
    }

    public function testDeleteNotExists()
    {
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
            'beforeDeleteCallback','afterDeleteCallback','afterCommitCallback',
        ], ['className' => Article::class]);
        $stub->initCallbacks();

        $stub->expects($this->once())
            ->method('beforeDeleteCallback')
            ->willReturn(true);

        $stub->expects($this->once())
            ->method('afterDeleteCallback');

        $stub->expects($this->once())
            ->method('afterCommitCallback');

        $this->assertTrue($stub->delete($article));
    }

    public function testDeleteFail()
    {
        $article = $this->Article->find('first');

        # Stub Model
        $stub = $this->getMockForModel('Article', [
            'beforeDeleteCallback','afterDeleteCallback','afterRollbackCallback','afterCommitCallback'
        ], ['className' => Article::class]);
        $stub->initCallbacks();
        $stub->expects($this->once())
            ->method('beforeDeleteCallback')
            ->willReturn(false);

        $stub->expects($this->never())
            ->method('afterDeleteCallback');

        $stub->expects($this->never())
            ->method('afterCommitCallback');

        $stub->expects($this->never())
            ->method('afterRollbackCallback');
            
        $this->assertFalse($stub->delete($article));
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
                'rating' => 5,
            ],
            'comments' => [
                ['description' => 'Save Comment #1'],
                ['description' => 'Save Comment #2'],
            ],
        ];

        $article = $this->Article->new($data);
        $this->assertInstanceOf(Entity::class, $article);
        $this->assertInstanceOf(Entity::class, $article->author);
        $this->assertInstanceOf(Entity::class, $article->comments[0]);

        // disable associations
        $article = $this->Article->new($data, ['associated' => false]);
        $this->assertNotEmpty($article->title);
        $this->assertTrue(is_array($article->author)); // test associated

        $article = $this->Article->new($data, ['fields' => ['title']]);
        $this->assertNotEmpty($article->title);
        $this->assertEmpty($article->body);
        $this->assertEmpty($article->author); // test associated

        $article = $this->Article->new($data, ['fields' => ['title','author'],'associated' => ['Author']]);
        $this->assertNotEmpty($article->title);
        $this->assertNotEmpty($article->author->location);

        $article = $this->Article->new($data, ['fields' => ['title','author'],'associated' => ['Author' => ['fields' => ['name']]]]);
        $this->assertNotEmpty($article->title);
        $this->assertNotEmpty($article->author->name);
        $this->assertEmpty($article->author->location);
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
        $this->assertTrue($this->Article->save($article, ['transaction' => false]));
        $this->Article->rollback();
        $this->assertEquals(3, $this->Article->find('count'));

        $article = $this->Article->new($data);
        $this->Article->begin();
        $this->assertTrue($this->Article->save($article, ['transaction' => false]));
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
            'begin','rollback','processSave',
        ], ['className' => Article::class]);

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
            'begin','commit','processSave',
        ], ['className' => Article::class]);

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
            'begin','commit','processSave',
        ], ['className' => Article::class]);
        $stub->expects($this->once())->method('processSave')->willReturn(true);
        $stub->expects($this->never())->method('begin');
        $stub->expects($this->never())->method('commit');
        $this->assertTrue($stub->save($article, ['transaction' => false]));

        $stub = $this->getMockForModel('Article', [
            'begin','rollback','processSave',
        ], ['className' => Article::class]);
        $stub->expects($this->once())->method('processSave')->willReturn(false);
        $stub->expects($this->never())->method('begin');
        $stub->expects($this->never())->method('rollback');
        $this->assertFalse($stub->save($article, ['transaction' => false]));
    }
}
