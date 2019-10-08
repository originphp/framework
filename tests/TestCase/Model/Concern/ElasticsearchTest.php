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

namespace Origin\Test\Model\Concern;

use Origin\Model\Model;

use Origin\TestSuite\TestTrait;
use Origin\TestSuite\OriginTestCase;

use Origin\Model\Concern\Elasticsearch;
use Origin\Model\Concern\Timestampable;

class Article extends Model
{
    use TestTrait, Elasticsearch, Timestampable;
    protected $elasticsearchConnection = 'test';
}

class ElasticsearchTest extends OriginTestCase
{
    protected $fixtures = ['Origin.Article'];

    protected function setUp(): void
    {
        $this->Article = $this->loadModel('Article', ['className' => Article::class]);

        if (env('ELASTICSEARCH_HOST') === null) {
            $this->markTestSkipped('Elasticsearch not available');
        }
    }

    public function testIndex()
    {
        $this->Article->index('title', ['type' => 'string']);
        $this->Article->index('body');
        $expected = [
            'title' => ['type' => 'string'],
            'body' => ['type' => 'text'],
        ];
        $result = $this->Article->getProperty('indexes');
      
        $this->assertEquals($expected, $result);
    }

    public function testIndexSettings()
    {
        $expected = ['number_of_shards' => 1];
        $this->Article->indexSettings($expected);
        $this->assertEquals($expected, $this->Article->getProperty('indexSettings'));
    }

    /**
     * Ensure that this works all together
     *
     * @return void
     */
    public function testReindex()
    {
        $this->assertEquals(3, $this->Article->reindex());
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
            'author_id' => 1002,
            'title' => 'Some Random Title',
            'body' => 'A description for this post',
            'created' => now(),
            'modified' => now(),
        ];
        $record = $this->Article->new($record);
        $this->assertTrue($this->Article->save($record));

        $this->wait();
        $result = $this->Article->search('Some Random Title');
        $this->assertEquals($record->id, $result[0]->id);
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
