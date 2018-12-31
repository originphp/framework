<?php
/**
 * OriginPHP Framework
 * Copyright 2018 Jamiel Sharief.
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

class DatasourceTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        require CONFIG.DS.'database.php';

        $this->connection = ConnectionManager::get('test');

        $this->createTables();
    }

    public function testIsVirtualField()
    {
        $this->assertTrue($this->connection->isVirtualField('Article__ref'));
        $this->assertFalse($this->connection->isVirtualField('article_ref'));
    }

    public function testCreate()
    {
        $sql = "INSERT INTO authors (name, description, created,modified)  VALUES ('Roger', 'New Author', NOW(),NOW());";
        $this->assertTrue($this->connection->execute($sql));
        $this->assertEquals(3, $this->connection->lastInsertId());
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
        $expected = array('id' => 1, 'name' => 'Tony', 'description' => 'a great guy');
        $this->assertEquals($expected, $this->connection->fetch());

        $sql = 'SELECT Author.id , Author.name, Author.description FROM authors AS Author LIMIT 1';
        $this->assertTrue($this->connection->execute($sql));
        $expected = array('Author' => ['id' => 1, 'name' => 'Tony', 'description' => 'a great guy']);
        $result = $this->connection->fetch('model');

        $this->assertEquals($expected, $result);

        $sql = 'SELECT Article.id, Article.title,Author.id,Author.name, CONCAT(Article.id, ":", Author.name) AS Article__ref FROM `articles` AS Article LEFT JOIN `authors` AS Author ON ( Article.author_id = Author.id )  WHERE Article.id = 1 LIMIT 1';
        $this->assertTrue($this->connection->execute($sql));
        $result = $this->connection->fetch('model');

        // Check Virtual fields
        $this->assertArrayHasKey('Article', $result);
        $this->assertArrayHasKey('ref', $result['Article']);
        $this->assertEquals('1:Tony', $result['Article']['ref']);

        // Check Join fields
        $this->assertArrayHasKey('Author', $result);
        $this->assertArrayHasKey('name', $result['Author']);
        $this->assertEquals('Tony', $result['Author']['name']);

        // Add This Again as it would of been dropped
        $sql = "INSERT INTO authors (name, description, created,modified)  VALUES ('Roger', 'New Author', NOW(),NOW());";
        $this->assertTrue($this->connection->execute($sql));

        // Read Many
        $sql = 'SELECT id, name, description FROM authors';
        $this->assertTrue($this->connection->execute($sql));
        $result = $this->connection->fetchAll();

        $this->assertEquals(3, count($result));
        $this->assertEquals('Tony', $result[0]['name']);
        $this->assertEquals('Amanda', $result[1]['name']);

        $sql = 'SELECT Author.id , Author.name, Author.description FROM authors AS Author';
        $this->assertTrue($this->connection->execute($sql));
        $result = $this->connection->fetchAll('model');

        $this->assertEquals(3, count($result));
        $this->assertEquals('Tony', $result[0]['Author']['name']);
        $this->assertEquals('Amanda', $result[1]['Author']['name']);

        $sql = 'SELECT Article.id, Article.title,Author.id,Author.name, CONCAT(Article.id, ":", Author.name) AS Article__ref FROM `articles` AS Article LEFT JOIN `authors` AS Author ON ( Article.author_id = Author.id )';
        $this->assertTrue($this->connection->execute($sql));
        $result = $this->connection->fetchAll('model');

        // Check Virtual fields
        $this->assertArrayHasKey('ref', $result[0]['Article']);
        $this->assertArrayHasKey('ref', $result[1]['Article']);
        $this->assertArrayHasKey('ref', $result[2]['Article']);

        // Test Lists
        $sql = "INSERT INTO authors (name, description, created,modified)  VALUES ('Sean', 'upcoming author', '2018-11-30 14:30:00','2018-11-30 14:30:00');";

        $this->assertTrue($this->connection->execute($sql));
        $sql = 'SELECT id FROM authors ORDER BY id DESC';
        $expected = array(4, 3, 2, 1);
        $this->assertTrue($this->connection->execute($sql));
        $this->assertEquals($expected, $this->connection->fetchList());

        $sql = 'SELECT id,name FROM authors ORDER BY name';
        $expected = array(2 => 'Amanda', 1 => 'Tony', 3 => 'Roger', 4 => 'Sean');
        $this->assertTrue($this->connection->execute($sql));
        $this->assertEquals($expected, $this->connection->fetchList());

        $sql = 'SELECT id,name,created FROM authors ORDER BY name';
        $this->assertTrue($this->connection->execute($sql));

        $result = $this->connection->fetchList();

        $expected = array(2 => 'Amanda', 1 => 'Tony');
        $this->assertArrayHasKey('2018-11-23 12:30:00', $result);
        $this->assertEquals($expected, $result['2018-11-23 12:30:00']);

        $expected = array(4 => 'Sean');
        $this->assertArrayHasKey('2018-11-30 14:30:00', $result);
        $this->assertEquals($expected, $result['2018-11-30 14:30:00']);
    }

    /**
     * @depends testExecuteSelect
     */
    public function testExecuteUpdate()
    {
        $sql = "UPDATE authors SET name ='Anthony Robbins' WHERE name ='Tony'";
        $this->assertTrue($this->connection->execute($sql));
        $sql = 'SELECT name FROM authors WHERE id = 1';
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
        $expected = array(1, 'Tony', 'a great guy');
        $result = $this->connection->fetch('num');
        $this->assertEquals($expected, $result);
    }

    public function testFetchAssoc()
    {
        $sql = 'SELECT id, name, description FROM authors LIMIT 1';
        $this->assertTrue($this->connection->execute($sql));
        $expected = array('id' => 1, 'name' => 'Tony', 'description' => 'a great guy');
        $result = $this->connection->fetch('assoc');
        $this->assertEquals($expected, $result);
    }

    public function testFetchModel()
    {
        $sql = 'SELECT id, name, description FROM authors LIMIT 1';
        $this->assertTrue($this->connection->execute($sql));
        $expected = array('authors' => array('id' => 1, 'name' => 'Tony', 'description' => 'a great guy'));

        $result = $this->connection->fetch('model');
        $this->assertEquals($expected, $result);
    }

    public function testFetchObject()
    {
        $sql = 'SELECT id, name, description FROM authors LIMIT 1';
        $this->assertTrue($this->connection->execute($sql));
        $expected = array('id' => 1, 'name' => 'Tony', 'description' => 'a great guy');
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

    public function createTables()
    {
        $this->connection->execute('DROP TABLE IF EXISTS articles, authors');
        // create tables, add data
        $statements = $this->getStatements();
        foreach ($statements as $statement) {
            $this->connection->execute($statement);
        }
    }

    private function getStatements()
    {
        $sql = "
    CREATE TABLE articles (
     id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
     author_id INT(11) NOT NULL,
     title VARCHAR(50),
     body TEXT,
     created DATETIME DEFAULT NULL,
     modified DATETIME DEFAULT NULL
 );
 CREATE TABLE authors (
     id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
     name VARCHAR(50),
     description TEXT,
     created DATETIME DEFAULT NULL,
     modified DATETIME DEFAULT NULL
 );
 INSERT INTO authors (name, description, created,modified)  VALUES ('Tony', 'a great guy', '2018-11-23 12:30:00',NOW());
 INSERT INTO authors (name, description, created,modified) VALUES ('Amanda', 'a great gal', '2018-11-23 12:30:00',NOW());
 INSERT INTO articles (author_id, title, body, created,modified) VALUES (1,'The title', 'This is the post body.', NOW(),NOW());
 INSERT INTO articles (author_id, title, body, created,modified) VALUES (2,'A title once again', 'And the post body follows.', NOW(),NOW());
 INSERT INTO articles (author_id, title, body, created,modified)  VALUES (1,'Title strikes back', 'This is really exciting! Not.', NOW(),NOW());";

        return explode(";\n", $sql);
    }
}
