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

namespace Origin\Test\Model\Schema;

use Origin\Model\Schema\MysqlSchema;
use Origin\Model\Datasource;
use Origin\TestSuite\OriginTestCase;

class MysqlSchemaTest extends OriginTestCase
{
    public $fixtures = ['Origin.Post','Origin.User','Origin.Article'];

    public function testAddColumn()
    {
        $adapter = new MysqlSchema('test');
        $expected = 'ALTER TABLE apples ADD COLUMN colour VARCHAR(255)';
        
        $result = $adapter->addColumn('apples', 'colour', 'string');
        $this->assertEquals($expected, $result);
        
        // Test limit
        $result = $adapter->addColumn('apples', 'colour', 'string', ['limit'=>40]);
        $expected = 'ALTER TABLE apples ADD COLUMN colour VARCHAR(40)';
        $this->assertEquals($expected, $result);

        $result = $adapter->addColumn('apples', 'qty', 'integer', ['limit'=>3]);
        $expected = 'ALTER TABLE apples ADD COLUMN qty INT(3)';
        $this->assertEquals($expected, $result);

        # # # TEST DEFAULTS
        
        // test default not null
        $expected = 'ALTER TABLE apples ADD COLUMN colour VARCHAR(255) DEFAULT \'foo\' NOT NULL';
        $result = $adapter->addColumn('apples', 'colour', 'string', ['default'=>'foo','null'=>false]);
        $this->assertEquals($expected, $result);

        // test default null
        $expected = 'ALTER TABLE apples ADD COLUMN colour VARCHAR(255) DEFAULT \'foo\'';
        $result = $adapter->addColumn('apples', 'colour', 'string', ['default'=>'foo']);
        $this->assertEquals($expected, $result);

        // test null
        $expected = 'ALTER TABLE apples ADD COLUMN colour VARCHAR(255) DEFAULT NULL';
        $result = $adapter->addColumn('apples', 'colour', 'string', ['default'=>'']);
        $this->assertEquals($expected, $result);

        // test not null (no default)
        $expected = 'ALTER TABLE apples ADD COLUMN colour VARCHAR(255) NOT NULL';
        $result = $adapter->addColumn('apples', 'colour', 'string', ['null'=>false]);
        $this->assertEquals($expected, $result);
        
        // test precision
        $expected = 'ALTER TABLE apples ADD COLUMN price DECIMAL(7,0)';
        $result = $adapter->addColumn('apples', 'price', 'decimal', ['precision'=>7]);
        $this->assertEquals($expected, $result);

        $expected = 'ALTER TABLE apples ADD COLUMN price DECIMAL(8,2)';
        $result = $adapter->addColumn('apples', 'price', 'decimal', ['precision'=>8,'scale'=>2]);
        $this->assertEquals($expected, $result);

        if ($adapter->connection()->engine() === 'mysql') {
            $result = $adapter->addColumn('articles', 'category', 'string', ['default'=>'new','limit'=>40]);
            $this->assertTrue($adapter->connection()->execute($result));
        }

        # Test MySQL specials
        $expected = 'ALTER TABLE apples ADD COLUMN description TEXT';
        $result = $adapter->addColumn('apples', 'description', 'text');
        $this->assertEquals($expected, $result);

        $expected = 'ALTER TABLE apples ADD COLUMN description MEDIUMTEXT';
        $result = $adapter->addColumn('apples', 'description', 'text', ['limit'=>16777215]);
        $this->assertEquals($expected, $result);

        $expected = 'ALTER TABLE apples ADD COLUMN description LONGTEXT';
        $result = $adapter->addColumn('apples', 'description', 'text', ['limit'=>4294967295]);
        $this->assertEquals($expected, $result);

        # Test MySQL specials
    }

    public function testAddForeignKey()
    {
        $adapter = new MysqlSchema('test');
        $expected = 'ALTER TABLE articles ADD CONSTRAINT fk_origin_12345 FOREIGN KEY (author_id) REFERENCES users (id)';
        $result = $adapter->addForeignKey('articles', 'users', [
            'primaryKey'=>'id',
            'name'=>'fk_origin_12345',
            'column' => 'author_id'
            ]);
        $this->assertEquals($expected, $result);

        if ($adapter->connection()->engine() === 'mysql') {
            $this->assertTrue($adapter->connection()->execute($result));
        }
    }

    public function testAddIndex()
    {
        $adapter = new MysqlSchema('test');
        $expected = 'CREATE INDEX owner_name ON accounts (owner_id)';
        $result = $adapter->addIndex('accounts', 'owner_id', 'owner_name');
        $this->assertEquals($expected, $result);

        $expected = 'CREATE INDEX owner_name ON accounts (owner_id, tenant_id)';
        $result = $adapter->addIndex('accounts', ['owner_id','tenant_id'], 'owner_name');
        $this->assertEquals($expected, $result);

        $expected = 'CREATE UNIQUE INDEX owner_name ON accounts (owner_id)';
        $result = $adapter->addIndex('accounts', 'owner_id', 'owner_name', ['unique'=>true]);
        $this->assertEquals($expected, $result);

        // Small sanity check
        if ($adapter->connection()->engine() === 'mysql') {
            $result = $adapter->addIndex('articles', 'title', 'index_title');
            $this->assertTrue($adapter->connection()->execute($result));
        }
    }
    public function testChangeColumn()
    {
        $adapter = new MysqlSchema('test');
        $expected = 'ALTER TABLE articles MODIFY COLUMN id INT(15)';
        $result = $adapter->changeColumn('articles', 'id', 'integer', ['limit'=>'15']);
        $this->assertEquals($expected, $result);

        # check for syntax errors
        if ($adapter->connection()->engine() === 'mysql') {
            $this->assertTrue($adapter->connection()->execute($result));
        }
    }

    public function testColumnExists()
    {
        $adapter = new MysqlSchema('test');
        if ($adapter->connection()->engine() !== 'mysql') {
            $this->markTestSkipped('This is test is for mysql');
        }
       
        $this->assertTrue($adapter->columnExists('posts', 'title'));
        $this->assertFalse($adapter->columnExists('posts', 'titles'));
    }

    public function testColumnName()
    {
        $adapter = new MysqlSchema('test');
        $this->assertEquals('foo', $adapter->columnName('foo'));
    }

    public function testColumnValue()
    {
        $adapter = new MysqlSchema('test');
        $this->assertEquals("'NULL'", $adapter->columnValue(null));
        $this->assertEquals(3, $adapter->columnValue(3));
        $this->assertEquals("'foo'", $adapter->columnValue('foo'));
    }

    public function testColumns()
    {
        $adapter = new MysqlSchema('test');
        if ($adapter->connection()->engine() !== 'mysql') {
            $this->markTestSkipped('This is test is for mysql');
        }
        $expected = ['id','title','body','published','created','modified'];
        $result = $adapter->columns('posts');
        $this->assertEquals($expected, $result);
    }

    public function testConnection()
    {
        $adapter = new MysqlSchema('test');
        $this->assertInstanceOf(Datasource::class, $adapter->connection());
    }

    public function testCreateTable()
    {
        $adapter = new MysqlSchema('test');
        $schema = [
            'id' => ['type'=>'primaryKey'],
            'name' => ['type'=>'string','default'=>'placeholder'],
            'description' => ['type'=>'text','null'=>false],
            'age' => ['type'=>'integer','default'=>1234],
            'bi' => ['type'=>'bigint'],
            'fn' => ['type'=>'float','precision'=>2], // ignored by postgres
            'dn' => ['type'=>'decimal','precision'=>8,'scale'=>2],
            'dt' => ['type'=>'datetime'],
            'ts' => ['type'=>'timestamp'],
            't' => ['type'=>'time'],
            'd' => ['type'=>'date'],
            'bf' => ['type'=>'binary'],
            'bool' => ['type'=>'boolean'],
        ];
        $result = $adapter->createTable('foo', $schema);
  
        $expected = 'f7b0aee6659379a452b3ee6d1a2d75eb';
      
        $this->assertEquals($expected, md5($result));

        # Sanity check
        if ($adapter->connection()->engine() === 'mysql') {
            $this->assertTrue($adapter->connection()->execute($result));
        }
    }

    public function testDatasource()
    {
        $adapter = new MysqlSchema('foo');
        $this->assertEquals('foo', $adapter->datasource());
        $adapter->datasource('bar');
        $this->assertEquals('bar', $adapter->datasource());
    }

    public function testDropTable()
    {
        $adapter = new MysqlSchema('test');
        $expected = 'DROP TABLE foo';
        $result = $adapter->dropTable('foo'); # created in createTable
        $this->assertEquals($expected, $result);
        $this->assertEquals('DROP TABLE IF EXISTS foo', $adapter->dropTable('foo', ['ifExists'=>true]));
        if ($adapter->connection()->engine() === 'mysql') {
            $this->assertTrue($adapter->connection()->execute($result));
        }
    }

    public function testForeignKeyExists()
    {
        $stub = $this->getMock(MysqlSchema::class, ['foreignKeys']);
        // Configure the stub.

        $foreignKeys = [
            ['constraint_name' => 'fk_origin_e74ce85cbc','column_name' => 'author_name'],
        ];

        $stub->method('foreignKeys')
             ->willReturn($foreignKeys);
        # Check logic
        $this->assertTrue($stub->foreignKeyExists('books', ['column'=>'author_name']));
        $this->assertTrue($stub->foreignKeyExists('books', ['name'=>'fk_origin_e74ce85cbc']));
        $this->assertFalse($stub->foreignKeyExists('books', ['column'=>'id']));
        $this->assertFalse($stub->foreignKeyExists('books', ['name'=>'fk_origin_e75ce86cb2']));
    }

    /**
     * @depends testAddForeignKey
     */
    public function testForeignKeys()
    {
        $adapter = new MysqlSchema('test');
        if ($adapter->connection()->engine() !== 'mysql') {
            $this->markTestSkipped('This test is for mysql');
        }

        $sql = $adapter->addForeignKey('articles', 'users', [
            'primaryKey'=>'id',
            'name'=>'fk_origin_12345',
            'column' => 'author_id'
            ]);
        $this->assertTrue($adapter->connection()->execute($sql));
       
        $expected = [
            'table_name' => 'articles',
            'column_name' => 'author_id',
            'constraint_name' => 'fk_origin_12345',
            'referenced_table_name' => 'users',
            'referenced_column_name' => 'id'
        ];
        $this->assertEquals([$expected], $adapter->foreignKeys('articles'));
    }

    public function testIndexes()
    {
        $adapter = new MysqlSchema('test');
        if ($adapter->connection()->engine() !== 'mysql') {
            $this->markTestSkipped('This test is for mysql');
        }
        $indexes = $adapter->indexes('articles');
        $expected =[
            'name' => 'PRIMARY',
            'column' => 'id',
            'unique' => true
        ];
        $this->assertEquals($expected, $indexes[0]);
    }

    public function testRemoveColumn()
    {
        $adapter = new MysqlSchema('test');
        $expected = 'ALTER TABLE articles DROP COLUMN modified';
        $result = $adapter->removeColumn('articles', 'modified');
        $this->assertEquals($expected, $result);
    
        if ($adapter->connection()->engine() === 'mysql') {
            $this->assertTrue($adapter->connection()->execute($result));
        }
    }

    public function testRemoveColumns()
    {
        $adapter = new MysqlSchema('test');
       
        $expected = "ALTER TABLE articles\nDROP COLUMN created,\nDROP COLUMN modified";
        $result = $adapter->removeColumns('articles', ['created','modified']);
        $this->assertEquals($expected, $result);
 
        if ($adapter->connection()->engine() === 'mysql') {
            $this->assertTrue($adapter->connection()->execute($result));
        }
    }

    /**
     * @depends testAddForeignKey
     */
    public function testRemoveForeignKey()
    {
        $adapter = new MysqlSchema('test');
        $expected = 'ALTER TABLE articles DROP FOREIGN KEY fk_origin_12345';
        $result = $adapter->removeForeignKey('articles', 'fk_origin_12345');
        $this->assertEquals($expected, $result);

        if ($adapter->connection()->engine() === 'mysql') {
            $sql = $adapter->addForeignKey('articles', 'users', [
                'primaryKey'=>'id',
                'name'=>'fk_origin_12345',
                'column' => 'author_id'
                ]);
            $this->assertTrue($adapter->connection()->execute($sql));
            $this->assertTrue($adapter->connection()->execute($result));
        }
    }

    public function testRemoveIndex()
    {
        $adapter = new MysqlSchema('test');
        $expected = 'DROP INDEX author_id ON articles';
        $result = $adapter->removeIndex('articles', 'author_id');
        $this->assertEquals($expected, $result);
    }
    
    public function testRenameColumn()
    {
        $adapter = new MysqlSchema('test');
        /**
         * For MySQL comptability with older versions it needs to run some statements
         * this test for MySQL pgsql is a simple command
         */
        if ($adapter->connection()->engine() !== 'mysql') {
            $this->markTestSkipped('This test is for mysql');
        }

        $expected = 'ALTER TABLE articles CHANGE author_id user_id INT(11)';
        $result = $adapter->renameColumn('articles', 'author_id', 'user_id');
        $this->assertEquals($expected, $result);
        $this->assertTrue($adapter->connection()->execute($result));
    }

    /**
     * @depends testAddIndex
     */
    public function testRenameIndex()
    {
        $adapter = new MysqlSchema('test');
        $expected = 'ALTER TABLE articles RENAME INDEX search_title TO title_search';
        $result = $adapter->renameIndex('articles', 'search_title', 'title_search');
        $this->assertEquals($expected, $result);

        if ($adapter->connection()->engine() === 'mysql') {
            $sql = $adapter->addIndex('articles', 'title', 'search_title');
            $this->assertTrue($adapter->connection()->execute($sql));
            $this->assertTrue($adapter->connection()->execute($result));
        }
    }
    
    public function testRenameTable()
    {
        $adapter = new MysqlSchema('test');
        $expected = 'RENAME TABLE articles TO artikel';
        $result = $adapter->renameTable('articles', 'artikel');
        $this->assertEquals($expected, $result);
        if ($adapter->connection()->engine() === 'mysql') {
            $this->assertTrue($adapter->connection()->execute($result));
            $result = $adapter->renameTable('artikel', 'articles');
            $this->assertTrue($adapter->connection()->execute($result)); // rename back for fixture manager
        }
    }
    
    public function testSchema()
    {
        $adapter = new MysqlSchema('test');
        if ($adapter->connection()->engine() !== 'mysql') {
            $this->markTestSkipped('This test is for mysql');
        }
        $result = $adapter->schema('articles');
        $expected = '642b0690d11a2147cbb322ebd7fed3ad'; // Any slight change, needs to be investigated
        $this->assertEquals($expected, md5(json_encode($result)));
    }

    public function testShowCreateTable()
    {
        $adapter = new MysqlSchema('test');
        if ($adapter->connection()->engine() !== 'mysql') {
            $this->markTestSkipped('This test is for mysql');
        }

        $result = $adapter->showCreateTable('articles');
        // Different mysql versions e.g. 5.x vs 8 will return slightly different result
        $expected = "CREATE TABLE `articles` (\n  `id` int(11) NOT NULL AUTO_INCREMENT,\n  `author_id` int(11) DEFAULT NULL,\n  `title` varchar(255) NOT NULL,\n  `body` text,\n  `created` datetime DEFAULT NULL,\n  `modified` datetime DEFAULT NULL,\n  PRIMARY KEY (`id`)\n)"; // Any slight change, needs to be investigated
        $this->assertContains($expected, $result);
    }

    public function testTableExists()
    {
        $adapter = new MysqlSchema('test');
        if ($adapter->connection()->engine() !== 'mysql') {
            $this->markTestSkipped('This test is for mysql');
        }
        $this->assertTrue($adapter->tableExists('articles'));
        $this->assertFalse($adapter->tableExists('foos'));
    }

    public function testTables()
    {
        $adapter = new MysqlSchema('test');
        if ($adapter->connection()->engine() !== 'mysql') {
            $this->markTestSkipped('This test is for mysql');
        }
        $this->assertEquals(['articles','posts','users'], $adapter->tables());
    }
}
