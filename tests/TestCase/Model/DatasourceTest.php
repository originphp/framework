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

use Origin\Model\ConnectionManager;
use Origin\Model\Engine\MySQLEngine;

use Origin\Model\Exception\DatasourceException;
use Origin\TestSuite\OriginTestCase;
use Origin\Model\Exception\ConnectionException;

class DatasourceTest extends OriginTestCase
{
    public $fixtures = ['Origin.Author','Origin.Article'];
    
    protected function setUp(): void
    {
        $this->connection = ConnectionManager::get('test');
    }
    public function testIsVirtualField()
    {
        $this->assertTrue($this->connection->isVirtualField('articles__ref'));
        $this->assertFalse($this->connection->isVirtualField('article_ref'));
    }

    public function testCreate()
    {
        $data = [
            'name' => 'Roger',
            'description' => 'New Author',
            'created' => now(),
            'modified' => now()
        ];
        $this->assertTrue($this->connection->insert('authors', $data));

        $data = [
            'name' => 'Roger Again',
            'description' => 'New Author',
            'created' => now(),
            'modified' => now()
        ];
        $this->assertTrue($this->connection->insert('authors', $data));
        $this->assertTrue($this->connection->lastInsertId()>1);
    }

    public function testConnectionException()
    {
        $config =  ConnectionManager::config('test');
        $config['password'] = 'fozzywozzy';
        $this->expectException(ConnectionException::class);
        $ds = new MySQLEngine();
        $ds->connect($config);
    }


    public function testExecuteException()
    {
        $this->expectException(DatasourceException::class);
        $this->connection->execute('select * from funky table');
    }

    public function _testDisconnect()
    {
        $this->assertTrue($this->connection->isConnected());
        $this->assertNull($this->connection->disconnect());
        $this->assertFalse($this->connection->isConnected());
        ConnectionManager::drop('test');
    }

    public function testLog()
    {
        $this->connection->execute('SELECT id, name, description FROM authors LIMIT 1');
        $this->assertNotEmpty($this->connection->log());
    }
    /**
     * @depends testCreate
     */
    public function testExecuteSelect()
    {
        $sql = 'SELECT name FROM authors WHERE id = 1024';
        $this->connection->execute($sql);
        $this->assertNull($this->connection->fetch());
 
        $sql = 'SELECT id, name, description FROM authors LIMIT 1';
        $this->assertTrue($this->connection->execute($sql));
        $expected = array('id' => 1000, 'name' => 'Author #1', 'description' => 'Description about Author #1');
        $this->assertEquals($expected, $this->connection->fetch());

        $sql = 'SELECT authors.id , authors.name, authors.description FROM authors LIMIT 1';
        $this->assertTrue($this->connection->execute($sql));
        $expected = array('authors' => array('id' => 1000, 'name' => 'Author #1', 'description' => 'Description about Author #1'));
        $result = $this->connection->fetch('model');

        $this->assertEquals($expected, $result);

        $sql = 'SELECT articles.id, articles.title,authors.id,authors.name, CONCAT(articles.id, \':\', authors.name) AS articles__ref FROM articles LEFT JOIN authors  ON ( articles.author_id = authors.id )  WHERE articles.id = 1000 LIMIT 1';
       
      
        $this->assertTrue($this->connection->execute($sql));
        $result = $this->connection->fetch('model');
        
        // Check Virtual fields
        $this->assertArrayHasKey('articles', $result);
        $this->assertArrayHasKey('ref', $result['articles']);
        $this->assertEquals('1000:Author #2', $result['articles']['ref']);

        // Check Join fields
        $this->assertArrayHasKey('authors', $result);
        $this->assertArrayHasKey('name', $result['authors']);
        $this->assertEquals('Author #2', $result['authors']['name']);


        // Read Many
        $sql = 'SELECT id, name, description FROM authors';
        $this->assertTrue($this->connection->execute($sql));
        $result = $this->connection->fetchAll();
        
        $this->assertEquals(3, count($result));
        $this->assertEquals('Author #1', $result[0]['name']);
        $this->assertEquals('Author #2', $result[1]['name']);

        $sql = 'SELECT authors.id , authors.name, authors.description FROM authors';
        $this->assertTrue($this->connection->execute($sql));
        $result = $this->connection->fetchAll('model');

        $this->assertEquals(3, count($result));
        $this->assertEquals('Author #1', $result[0]['authors']['name']);
        $this->assertEquals('Author #2', $result[1]['authors']['name']);


        $sql = 'SELECT articles.id, articles.title,authors.id,authors.name, CONCAT(articles.id, \':\', authors.name) AS articles__ref FROM articles LEFT JOIN authors  ON ( articles.author_id = authors.id )';
       
        $this->assertTrue($this->connection->execute($sql));
        $result = $this->connection->fetchAll('model');

        // Check Virtual fields
        $this->assertArrayHasKey('ref', $result[0]['articles']);
        $this->assertArrayHasKey('ref', $result[1]['articles']);
        $this->assertArrayHasKey('ref', $result[2]['articles']);

        $timestamp = now();
        // Test Lists
        $data = [
            'name' => 'Sean',
            'description' => 'upcoming author',
            'created' =>  '2019-03-27 13:12:00' ,
            'modified' => $timestamp
        ];
        $this->assertTrue($this->connection->insert('authors', $data));

        $lastId = $this->connection->lastInsertId();

        # Postgre and mysql returning different ids.
        $this->connection->update('authors', ['id'=>999], ['id'=>$lastId]);

        $sql = 'SELECT id FROM authors ORDER BY id DESC';
        $expected = array(1002, 1001, 1000,999);
        $this->assertTrue($this->connection->execute($sql));
        $this->assertEquals($expected, $this->connection->fetchList());

        $sql = 'SELECT id,name FROM authors ORDER BY id DESC';
        $expected = array(1002 => 'Author #3', 1001 => 'Author #2', 1000 => 'Author #1', 999=> 'Sean');
        $this->assertTrue($this->connection->execute($sql));
  
        $this->assertEquals($expected, $this->connection->fetchList());

        $sql = 'SELECT id,name,created FROM authors ORDER BY name';
        $this->assertTrue($this->connection->execute($sql));

        $result = $this->connection->fetchList();
       
        $expected = array(1002 => 'Author #3', 999 => 'Sean');
        $this->assertArrayHasKey('2019-03-27 13:12:00', $result);
        $this->assertEquals($expected, $result[ '2019-03-27 13:12:00']);

        $expected = array(1001 => 'Author #2');
        $this->assertArrayHasKey('2019-03-27 13:11:00', $result);
        $this->assertEquals($expected, $result[ '2019-03-27 13:11:00' ]);
    }

    /**
     * @depends testExecuteSelect
     */
    public function testExecuteUpdate()
    {
        $sql = "UPDATE authors SET name ='Anthony Robbins' WHERE name ='Author #1'";
        $this->assertTrue($this->connection->execute($sql));
        $sql = 'SELECT name FROM authors WHERE id = 1000';
        $this->connection->execute($sql);
        $result = $this->connection->fetch();
        $this->assertEquals('Anthony Robbins', $result['name']);
    }

    /**
     * @depends testExecuteUpdate
     */
    public function testExecuteDelete()
    {
        $sql = 'DELETE FROM authors WHERE id = 1';
        $this->assertTrue($this->connection->execute($sql));

        $sql = 'SELECT name FROM authors WHERE id = 1';
        $this->connection->execute($sql);
        $this->assertNull($this->connection->fetch());
    }

    public function testFetchNum()
    {
        $sql = 'SELECT id, name, description FROM authors LIMIT 1';
        $this->assertTrue($this->connection->execute($sql));
        $expected = array(1000, 'Author #1', 'Description about Author #1');
        $result = $this->connection->fetch('num');
        $this->assertEquals($expected, $result);
    }

    public function testFetchAssoc()
    {
        $sql = 'SELECT id, name, description FROM authors LIMIT 1';
        $this->assertTrue($this->connection->execute($sql));
       
        $expected = [
            'id' => 1000,
            'name' => 'Author #1',
            'description' => 'Description about Author #1'
        ];
        $result = $this->connection->fetch('assoc');
     
        $this->assertEquals($expected, $result);
    }

    public function testFetchModel()
    {
        $sql = 'SELECT id, name, description FROM authors LIMIT 1';
        $this->assertTrue($this->connection->execute($sql));
        $expected = [
            'id' => 1000,
            'name' => 'Author #1',
            'description' => 'Description about Author #1'
        ];
        $result = $this->connection->fetch('model');
        $this->assertEquals(['authors'=>$expected], $result);
    }

    public function testFetchObject()
    {
        $sql = 'SELECT id, name, description FROM authors LIMIT 1';
        $this->assertTrue($this->connection->execute($sql));
        $expected = [
            'id' => 1000,
            'name' => 'Author #1',
            'description' => 'Description about Author #1'
        ];
        $result = $this->connection->fetch('obj');
        $this->assertEquals((object) $expected, $result);
    }

    public function testInsert()
    {
        $data = array('author_id' => 1, 'title' => 'How to insert data', 'body' => 'Use this function', 'created' => date('Y-m-d'), 'modified' => date('Y-m-d'));
        $result = $this->connection->insert('articles', $data);
        $this->assertTrue($result);
    }

    public function testUpdate()
    {
        $data = array('title' => 'The title [updtaed]');
        $conditions = array('id' => 1);
        $result = $this->connection->update('articles', $data, $conditions);
        $this->assertTrue($result);
    }

    public function testDelete()
    {
        $conditions = array('id' => 2);
        $result = $this->connection->delete('articles', $conditions);
        $this->assertTrue($result);
    }

    public function testTables()
    {
        $tables = $this->connection->tables();
        $this->assertTrue(in_array('authors', $tables));
        $this->assertTrue(in_array('articles', $tables));
    }

    /**
     * Originally create table was part of Datasource
     * @todo write test for each adapter, and then rely on that for testin
     *
     * @return void
     */
    public function testCreateTable()
    {
        $this->connection->execute('DROP TABLE IF EXISTS foo;');

        $schema = [
            'id' => ['type'=>'primaryKey'],
            'name' => ['type'=>'string','default'=>'placeholder'],
            'description' => ['type'=>'text','null'=>false],
            'age' => ['type'=>'integer','default'=>1234],
            'bi' => ['type'=>'bigint'],
            'fn' => ['type'=>'float','precision'=>2], // ignored by postgres
            'dn' => ['type'=>'decimal','precision'=>2],
            'dt' => ['type'=>'datetime'],
            'ts' => ['type'=>'timestamp'],
            't' => ['type'=>'time'],
            'd' => ['type'=>'date'],
            'bf' => ['type'=>'binary'],
            'bool' => ['type'=>'boolean'],
        ];
        $sql = $this->connection->adapter()->createTable('foo', $schema);
        
        $this->assertTrue($this->connection->execute($sql));
    
        $schema = [
            'bookmarks_id' => ['type'=>'integer','key'=>'primary'],
            'tags_id' => ['type'=>'integer','key'=>'primary'],
        ];
        $sql = $this->connection->adapter()->createTable('bar', $schema);
  
        $this->assertContains('bookmarks_id INT', $sql);
        $this->assertContains('tags_id INT', $sql);
        $this->assertContains('PRIMARY KEY (bookmarks_id,tags_id)', $sql);

        $this->assertTrue($this->connection->execute($sql));
        $this->connection->execute('DROP TABLE bar');
    }

    /**
     * @depends testCreateTable
     *
     * @return void
     */
    public function testSchema()
    {
        $schema = $this->connection->schema('foo');
        $engine = $this->connection->engine();

        $this->assertEquals('primaryKey', $schema['id']['type']);
        $this->assertEquals('primary', $schema['id']['key']);
        $this->assertEquals('string', $schema['name']['type']);
        $this->assertEquals('placeholder', $schema['name']['default']);
        $this->assertEquals('text', $schema['description']['type']);
        $this->assertEquals('integer', $schema['age']['type']);
        $this->assertEquals(1234, $schema['age']['default']);
        $this->assertEquals('bigint', $schema['bi']['type']);
        $this->assertEquals('float', $schema['fn']['type']);
        if ($engine === 'mysql') {
            $this->assertEquals(2, $schema['fn']['precision']);
        }
        
        $this->assertEquals('decimal', $schema['dn']['type']);
        $this->assertEquals(2, $schema['dn']['precision']);
        $this->assertEquals('datetime', $schema['dt']['type']);
        if ($engine === 'mysql') {
            $this->assertEquals('timestamp', $schema['ts']['type']);
        } else {
            $this->assertEquals('datetime', $schema['ts']['type']);
        }
        
        $this->assertEquals('date', $schema['d']['type']);
        $this->assertEquals('time', $schema['t']['type']);
        $this->assertEquals('binary', $schema['bf']['type']);
        $this->assertEquals('boolean', $schema['bool']['type']);

        $this->connection->execute('DROP TABLE foo');
    }
}
