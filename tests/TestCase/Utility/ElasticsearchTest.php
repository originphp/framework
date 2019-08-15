<?php

namespace Origin\Test\Utility;

use Origin\Utility\Elasticsearch;
use Origin\Exception\NotFoundException;
use Origin\Utility\Exception\ElasticsearchException;

class ElasticsearchTest extends \PHPUnit\Framework\TestCase
{
    public function testAddIndex()
    {
        $elasticsearch = Elasticsearch::connection('test');
        $this->assertTrue($elasticsearch->addIndex('test_index'));
    }

    public function testIndexes()
    {
        $elasticsearch = Elasticsearch::connection('test');
        $result = $elasticsearch->indexes();
        $this->assertTrue(in_array('test_index', $result));
    }

    public function testIndexExists()
    {
        $elasticsearch = Elasticsearch::connection('test');
        $this->assertTrue($elasticsearch->indexExists('test_index'));
        $this->assertFalse($elasticsearch->indexExists('unkown_index'));
    }

    public function testIndex()
    {
        $elasticsearch = Elasticsearch::connection('test');
        $this->assertTrue($elasticsearch->indexExists('test_index'));
        $this->assertFalse($elasticsearch->indexExists('unkown_index'));
    }

    public function testRemoveIndex()
    {
        $elasticsearch = Elasticsearch::connection('test');
        $this->assertTrue($elasticsearch->removeIndex('test_index'));
        $this->assertFalse($elasticsearch->indexExists('test_index'));
    }

    public function testAdd()
    {
        $elasticsearch = Elasticsearch::connection('test');
        $data = ['title' => 'how to use elasticsearch','body' => 'some article content goes here'];
        $this->assertTrue($elasticsearch->add('test_posts', 1000, $data));
        $data = ['title' => 'what is Elasticsearch','body' => 'some article content goes here'];
        $this->assertTrue($elasticsearch->add('test_posts', 1001, $data));
        $data = ['title' => 'open source search and analytics engine','body' => 'Check out elasticsearch'];
        $this->assertTrue($elasticsearch->add('test_posts', 1002, $data));
        sleep(1); // # Important! if not tests fails afterwards
        $this->expectException(ElasticsearchException::class);
        $elasticsearch->add('test_posts', 1234, ['abc']);
    }

    public function testGet()
    {
        $elasticsearch = Elasticsearch::connection('test');
        $record = $elasticsearch->get('test_posts', 1000);
        $this->assertEquals('how to use elasticsearch', $record['title']);
        $this->expectException(NotFoundException::class);
        $elasticsearch->get('test_posts', 100000000);
    }

    public function testExists()
    {
        $elasticsearch = Elasticsearch::connection('test');
        $this->assertTrue($elasticsearch->exists('test_posts', 1000));
        $this->assertFalse($elasticsearch->exists('test_posts', 2000));
    }

    public function testCount()
    {
        $elasticsearch = Elasticsearch::connection('test');
        $this->assertEquals(3, $elasticsearch->count('test_posts'));
        $this->assertEquals(2, $elasticsearch->count('test_posts', ['title' => 'elasticsearch']));
        $this->expectException(ElasticsearchException::class);
        $elasticsearch->count('foo');
    }

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

    public function testDelete()
    {
        $elasticsearch = Elasticsearch::connection('test');
        $this->assertTrue($elasticsearch->delete('test_posts', 1000));
        $this->assertFalse($elasticsearch->delete('test_posts', 1000));
    }
}
