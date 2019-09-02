<?php

namespace Origin\Test\Utility;

use Origin\Utility\Elasticsearch;
use Origin\Utility\Exception\NotFoundException;
use Origin\Utility\Exception\ElasticsearchException;

class ElasticsearchTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp() : void
    {
        if (env('ELASTICSEARCH_HOST') === null) {
            $this->markTestSkipped('Elasticsearch not available');
        }
    }
    public function testConnection()
    {
        $elasticsearch = Elasticsearch::connection('test');
        $this->assertInstanceOf(Elasticsearch::class, $elasticsearch);
    }
    /**
     * @depends testConnection
     */
    public function testInvalidConnection()
    {
        $this->expectException(ElasticsearchException::class);
        $elasticsearch = Elasticsearch::connection('foo');
    }
    /**
     * @depends testConnection
     */
    public function testAddIndex()
    {
        $elasticsearch = Elasticsearch::connection('test');
        $this->assertTrue($elasticsearch->addIndex('test_index'));

        $this->expectException(ElasticsearchException::class);
        $elasticsearch->addIndex('__invalid name * +');
    }

    /**
     * @depends testConnection
     */
    public function testIndexes()
    {
        $elasticsearch = Elasticsearch::connection('test');
        $result = $elasticsearch->indexes();
        $this->assertTrue(in_array('test_index', $result));
    }
    /**
     * @depends testConnection
     */
    public function testIndexExists()
    {
        $elasticsearch = Elasticsearch::connection('test');
        $this->assertTrue($elasticsearch->indexExists('test_index'));
        $this->assertFalse($elasticsearch->indexExists('unkown_index'));
    }
    /**
     * @depends testConnection
     */
    public function testGetIndex()
    {
        $elasticsearch = Elasticsearch::connection('test');
        $index = $elasticsearch->getIndex('test_index');
        $this->assertNotEmpty($index['settings']['index']);
    }
    /**
     * @depends testConnection
     */
    public function testRemoveIndex()
    {
        $elasticsearch = Elasticsearch::connection('test');
        $this->assertTrue($elasticsearch->removeIndex('test_index'));
        $this->assertFalse($elasticsearch->indexExists('test_index'));

        $this->expectException(ElasticsearchException::class);
        $this->assertTrue($elasticsearch->removeIndex('index_that_does_not_exist'));
    }
    public function testResponse()
    {
        $elasticsearch = Elasticsearch::connection('test');
        $this->assertFalse($elasticsearch->indexExists('unkown_index'));
        $response = ['statusCode' => 404,'body' => null];
        $this->assertEquals($response, $elasticsearch->response());
    }
    /**
     * @depends testConnection
     */
    public function testAdd()
    {
        $elasticsearch = Elasticsearch::connection('test');
        $data = ['title' => 'how to use elasticsearch','body' => 'some article content goes here'];
        $this->assertTrue($elasticsearch->index('test_posts', 1000, $data));
        $data = ['title' => 'what is Elasticsearch','body' => 'some article content goes here'];
        $this->assertTrue($elasticsearch->index('test_posts', 1001, $data));
        $data = ['title' => 'open source search and analytics engine','body' => 'Check out elasticsearch'];
        $this->assertTrue($elasticsearch->index('test_posts', 1002, $data));
        sleep(1); // # Important! if not tests fails afterwards
        $this->expectException(ElasticsearchException::class);
        $elasticsearch->index('test_posts', 1234, ['abc']);
    }
    /**
     * @depends testConnection
     */
    public function testGet()
    {
        $elasticsearch = Elasticsearch::connection('test');
        $record = $elasticsearch->get('test_posts', 1000);
        $this->assertEquals('how to use elasticsearch', $record['title']);
        $this->expectException(NotFoundException::class);
        $elasticsearch->get('test_posts', 100000000);
    }

    /**
        * @depends testConnection
        */
    public function testGetException()
    {
        $this->expectException(ElasticsearchException::class);
        $elasticsearch = Elasticsearch::connection('test');
        $record = $elasticsearch->get('invalid_index', 1000);
    }

    /**
     * @depends testConnection
     */
    public function testExists()
    {
        $elasticsearch = Elasticsearch::connection('test');
        $this->assertTrue($elasticsearch->exists('test_posts', 1000));
        $this->assertFalse($elasticsearch->exists('test_posts', 2000));
    }
    /**
     * @depends testConnection
     */
    public function testCount()
    {
        $elasticsearch = Elasticsearch::connection('test');
        $this->assertEquals(3, $elasticsearch->count('test_posts'));
        $this->assertEquals(2, $elasticsearch->count('test_posts', ['title' => 'elasticsearch']));
        $this->expectException(ElasticsearchException::class);
        $elasticsearch->count('foo');
    }
    /**
     * @depends testConnection
     */
    public function testSearch()
    {
        $elasticsearch = Elasticsearch::connection('test');
       
        $query = ['query' => ['multi_match' => ['query' => 'elasticsearch']]];
        $result = $elasticsearch->search('test_posts', $query);
        $this->assertEquals(3, count($result));

        $result = $elasticsearch->search('test_posts', 'analytics');
        $this->assertEquals(1, count($result));

        $result = $elasticsearch->search('test_posts', 'body:analytics');
        $this->assertEquals(0, count($result));

        // Make sure some searches are working
        $result = $elasticsearch->search('test_posts', '+analytics +engine');
        $this->assertEquals(1, count($result));

        $result = $elasticsearch->search('test_posts', 'engine analytics');
        $this->assertEquals(1, count($result));

        $result = $elasticsearch->search('test_posts', '"engine analytics"');
        $this->assertEquals(0, count($result));

        $result = $elasticsearch->search('test_posts', '+analytics -engine');
        $this->assertEquals(0, count($result));
    }
    /**
     * @depends testConnection
     */
    public function testDelete()
    {
        $elasticsearch = Elasticsearch::connection('test');
        $this->assertTrue($elasticsearch->deindex('test_posts', 1000));
        $this->assertFalse($elasticsearch->deindex('test_posts', 1000));
        sleep(1);
        $this->expectException(ElasticsearchException::class);
        $elasticsearch->deindex('___', 1000);
    }

    public function testDeleteAll()
    {
        $elasticsearch = Elasticsearch::connection('test');
        $this->assertEquals(2, $elasticsearch->count('test_posts'));
        $this->assertTrue($elasticsearch->deindex('test_posts', [1001,1002]));
        sleep(1);
        $this->assertEquals(0, $elasticsearch->count('test_posts'));
    }
}
