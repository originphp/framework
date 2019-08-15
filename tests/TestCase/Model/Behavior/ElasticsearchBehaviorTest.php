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

namespace Origin\Test\Model\Behavior;

use Origin\Model\Model;
use Origin\Exception\Exception;

use Origin\TestSuite\TestTrait;
use Origin\TestSuite\OriginTestCase;
use Origin\Model\Behavior\ElasticsearchBehavior;

class Article extends Model
{
    use TestTrait;
    public function initialize(array $config)
    {
        $this->loadBehavior('Timestamp');
        $this->loadBehavior('Elasticsearch', [
            'className' => __NAMESPACE__ . '\MockElasticsearchBehavior',
            'connection' => 'test',
        ]);
    }
}

class MockElasticsearchBehavior extends ElasticsearchBehavior
{
    use TestTrait;
}

class ElasticsearchBehaviorTest extends OriginTestCase
{
    public $fixtures = ['Origin.Article'];

    public function setUp(): void
    {
        parent::setUp();
        $this->Article = new Article(['datasource' => 'test']);
    }

    public function testIndex()
    {
        $this->Article->index('title', ['type' => 'string']);
        $this->Article->index('body');
        $expected = [
            'title' => ['type' => 'string'],
            'body' => ['type' => 'text'],
        ];
        $result = $this->Article->Elasticsearch->getProperty('indexes');
      
        $this->assertEquals($expected, $result);

        $this->expectException(Exception::class);
        $this->Article->index('foo');
    }

    public function testIndexSettings()
    {
        $expected = ['number_of_shards' => 1];
        $this->Article->indexSettings($expected);
        $this->assertEquals($expected, $this->Article->Elasticsearch->getProperty('indexSettings'));
    }

    /**
     * Ensure that this works all together
     *
     * @return void
     */
    public function testReCreateIndex()
    {
        $this->Article->deleteIndex();
        $this->assertTrue($this->Article->createIndex());
        $this->assertEquals(3, $this->Article->import());
        $this->wait(); # Give a small delay for es
    }

    public function testSearch()
    {
        $result = $this->Article->search('#2');
        $this->assertEquals(1001, $result[0]->id);
        $this->assertEquals(1, count($result));

        $result = $this->Article->search('article');
        $this->assertEquals(3, count($result));
    }

    public function testAdvancedSearch()
    {
        $query = ['must' => ['term' => ['title' => 'Article #2']]];
        $result = $this->Article->search(['query' => ['bool' => $query]]);
        $this->assertEquals(1001, $result[0]->id);
        $this->assertEquals(1, count($result));
    }

    public function testAfterSave()
    {
        $record = [
            'id' => 1234,
            'author_id' => 1002,
            'title' => 'Some Random Title',
            'body' => 'A description for this post',
            'created' => now(),
            'modified' => now(),
        ];
        $record = $this->Article->new($record);
        $this->assertTrue($this->Article->save($record));
        $this->wait();
        $result = $this->Article->search('Some Random Title', 'title');
        $this->assertEquals(1234, $result[0]->id);
    }

    public function testAfterDelete()
    {
        $result = $this->Article->search('#2');
        $this->assertNotEmpty($result);

        $article = $this->Article->get(1001);
        $this->assertTrue($this->Article->delete($article));
        
        $this->wait();
        
        $result = $this->Article->search('#2');
        $this->assertEmpty($result);
    }

    /**
     * small delays help the tests work.
     *
     * @return void
     */
    protected function wait()
    {
        sleep(1);
    }
}
