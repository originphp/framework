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

use Origin\Model\Model;

use Origin\Model\Entity;

use Origin\Model\Collection;
use Origin\TestSuite\OriginTestCase;
use Origin\Core\Exception\InvalidArgumentException;

class QueryArticle extends Model
{
    protected $table = 'articles';
    protected function initialize(array $config): void
    {
        $this->belongsTo('Author', ['className' => QueryAuthor::class]);
    }
}

class QueryAuthor extends Model
{
    protected $table = 'authors';
    protected function initialize(array $config): void
    {
        $this->hasMany('Article', ['className' => QueryArticle::class]);
    }
}

class QueryTest extends OriginTestCase
{
    public $fixtures = ['Origin.Article','Origin.Author'];

    public function setUp(): void
    {
        $this->Article = $this->loadModel('Article', ['className' => QueryArticle::class]);
    }

    public function testSelect()
    {
        $query = $this->Article->where(['id' => 1001])->select(['id']);
        $this->assertEquals('SELECT articles.id FROM `articles` WHERE articles.id = :a0', $query->sql());
    }

    public function testIteration()
    {
        $query = $this->Article->select(['id']);
        $count = 0;
        foreach ($query as $article) {
            $count ++;
        }
        $this->assertEquals(3, $count);
    }

    public function testDebugInfo()
    {
        $query = $this->Article->select(['id']);
        $expected = [
            'conditions' => [],
            'fields' => ['id'],
            'joins' => [],
            'order' => null,
            'limit' => null,
            'group' => null,
            'page' => null,
            'offset' => null,
            'associated' => []
        ];
        $this->assertEquals($expected, $query->__debugInfo());
    }

    public function testSelectDistinct()
    {
        $query = $this->Article->where(['id' => 1001])->select(['id'])->distinct();
        $this->assertEquals('SELECT DISTINCT articles.id FROM `articles` WHERE articles.id = :a0', $query->sql());
    }

    public function testWhere()
    {
        $query = $this->Article->select(['id'])->where(['id' => 1001]);
        $this->assertEquals('SELECT articles.id FROM `articles` WHERE articles.id = :a0', $query->sql());
    }

    public function testOrder()
    {
        $expected = 'SELECT articles.id FROM `articles` ORDER BY id DESC';
       
        $query = $this->Article->select(['id'])->order('id DESC');
        $this->assertEquals($expected, $query->sql());

        $query = $this->Article->select(['id'])->order(['id DESC']);
        $this->assertEquals($expected, $query->sql());

        $expected = 'SELECT articles.id FROM `articles` ORDER BY articles.id DESC';

        $query = $this->Article->select(['id'])->order(['id' => 'DESC']);
        $this->assertEquals($expected, $query->sql());
    }

    /**
     * @depends testOrder
     * @depends testSelect
     */
    public function testQueryFields()
    {
        /**
        * Test all fields selected
        */
        $query = $this->Article->where(['id' => 1001])->order('id ASC');
        $expected = 'SELECT articles.id, articles.author_id, articles.title, articles.body, articles.created, articles.modified FROM `articles` WHERE articles.id = :a0 ORDER BY id ASC';
        $this->assertEquals($expected, $query->sql());
    }

    public function testLimit()
    {
        $query = $this->Article->select(['id'])->limit(2);
        $this->assertEquals('SELECT articles.id FROM `articles` LIMIT 2', $query->sql());
    }

    public function testLimitOffset()
    {
        $query = $this->Article->select(['id'])->limit(1)->offset(1);
        $this->assertEquals('SELECT articles.id FROM `articles` LIMIT 1 OFFSET 1', $query->sql());
    }

    public function testGroup()
    {
        $expected = 'SELECT count(id) as count, articles.author_id FROM `articles` GROUP BY articles.author_id';

        $query = $this->Article->select(['count(id) as count','author_id'])->group('author_id');
        $this->assertEquals($expected, $query->sql());

        $query = $this->Article->select(['count(id) as count','author_id'])->group(['author_id']);
        $this->assertEquals($expected, $query->sql());
    }

    public function testHaving()
    {
        $query = $this->Article->select(['count(id) as count','author_id'])->group('author_id')->having(['author_id > 1']);
        $this->assertEquals(
            'SELECT count(id) as count, articles.author_id FROM `articles` GROUP BY articles.author_id HAVING author_id > 1',
            $query->sql()
        );
    }

    public function testLock()
    {
        $query = $this->Article->select(['id','title'])->lock();
        $this->assertEquals('SELECT articles.id, articles.title FROM `articles` FOR UPDATE', $query->sql());
    }

    public function testJoin()
    {
        $expected = 'SELECT articles.id, articles.author_id, articles.title, authors.id, authors.name FROM `articles` INNER JOIN `authors` ON (articles.author_id = authors.id)';
      
        $query = $this->Article->select(['id', 'author_id', 'title', 'authors.id', 'authors.name'])->join('authors');
        $this->assertEquals($expected, $query->sql());
       
        $query = $this->Article->select(['id', 'author_id', 'title', 'authors.id', 'authors.name'])->join(['table' => 'authors']);
        $this->assertEquals($expected, $query->sql());

        $this->expectException(InvalidArgumentException::class);
        $this->Article->select(['id', 'author_id', 'title', 'authors.id', 'authors.name'])->join(['foo' => 'bar']);
    }

    public function testWith()
    {
        /**
         * Test selecting selected fields as well
         */
        $expected = 'SELECT articles.id, articles.author_id, articles.title, authors.id, authors.name FROM `articles`';
        $query = $this->Article->select(['id', 'author_id', 'title', 'authors.id', 'authors.name'])->with('Author');
        $this->assertEquals($expected, $query->sql());

        /**
         * Test get all fields
         */
        $expected = 'SELECT articles.id, articles.author_id, articles.title, articles.body, articles.created, articles.modified FROM `articles` WHERE articles.id != :a0';
        $query = $this->Article->where(['id !=' => 1000])->with('Author');
        $this->assertEquals($expected, $query->sql());
    }

    /**
     * Check its all working okay.  The model will fetch the fields automatically if the fields value is null, not empty
     *
     * @return void
     */
    public function testWithNormalize()
    {
        $query = $this->Article->where(['id' => 1000])->with('Author');
        $this->assertNull($query->toArray()['associated']['Author']['fields']);
       
        $query = $this->Article->where(['id' => 1000])->select(['id','title'])->with('Author');
      
        $this->assertEquals([], $query->toArray()['associated']['Author']['fields']);

        # As no fields are selected the value of Field in array should be NULL
        $query = $this->Article->where(['id' => 1000])->with(['Author' => ['Comment' => ['User']]]);
        $this->assertSame(3817391946, crc32(serialize($query->toArray())));

        # As fields are selected the value of Field in array should be []
        $query = $this->Article->where(['id' => 1000])->select(['id'])->with(['Author' => ['Comment' => ['User']]]);
        $this->assertSame(1342860342, crc32(serialize($query->toArray())));
    }

    public function testAll()
    {
        $query = $this->Article->select(['id','title']);
        $this->assertInstanceOf(Collection::class, $query->all());
    }

    public function testFirst()
    {
        $query = $this->Article->select(['id','title']);
        $this->assertInstanceOf(Entity::class, $query->first());
    }

    public function testCount()
    {
        $query = $this->Article->select(['id','title']);
        $this->assertEquals(3, $query->count());
    }

    public function testSum()
    {
        $query = $this->Article->select(['id','title']);
        $this->assertEquals(3003, $query->sum('id'));
    }

    public function testAverage()
    {
        $query = $this->Article->select(['id','title']);
        $this->assertEquals(1001, $query->average('id'));
    }

    public function testMin()
    {
        $query = $this->Article->select(['id','title']);
        $this->assertEquals(1000, $query->minimum('id'));
    }
    public function testMax()
    {
        $query = $this->Article->select(['id','title']);
        $this->assertEquals(1002, $query->maximum('id'));
    }
}
