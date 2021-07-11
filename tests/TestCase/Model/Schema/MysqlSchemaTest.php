<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2021 Jamiel Sharief.
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

use Origin\Model\Connection;
use Origin\Model\Schema\MysqlSchema;
use Origin\TestSuite\OriginTestCase;

class MysqlSchemaTest extends OriginTestCase
{
    protected $fixtures = ['Origin.Post','Origin.User','Origin.Article','Origin.Deal'];

    public function testCreateTableColumns()
    {
        // todo unsigned
        $adapter = new MysqlSchema('test');
        $schema = [
            'f0' => ['type' => 'integer','autoIncrement' => true,'comment' => 'This will be primary key'],
            'f1' => ['type' => 'string','limit' => 80,'null' => false],
            'f2' => ['type' => 'string', 'fixed' => true],
            'f3' => 'text',
            'f4' => ['type' => 'integer','default' => 100],
            'f5' => ['type' => 'float','precision' => 8,'scale' => 2],
            'f6' => ['type' => 'decimal','precision' => 10,'scale' => 3],
            'f7' => 'datetime',
            'f8' => 'time',
            'f9' => ['type' => 'timestamp','null' => true],
            'f10' => 'date',
            'f11' => 'binary',
            'f12' => 'boolean',
            'f13' => ['type' => 'bigint','default' => 10000,'null' => false], // test bigint/default/null=false
            'f14' => ['type' => 'integer','limit' => 12,'unsigned' => true],
            'f15' => ['type' => 'string','default' => 'abc'],
            'f16' => ['type' => 'text','limit' => MysqlSchema::MEDIUMTEXT],
            'f17' => ['type' => 'timestamp','default' => 'CURRENT_TIMESTAMP','null' => false], // different default behaviors on different versions
            
        ];
        /**
         * Constraints and indexes are needed for next
         */
        $options = [
            'constraints' => [
                'p' => ['type' => 'primary','column' => ['f0']],
                'u1' => ['type' => 'unique','column' => ['f1']],
            ],
            'indexes' => [
                'u2' => ['type' => 'index','column' => ['f2']],
                'u3' => ['type' => 'fulltext','column' => ['f2','f3']],
            ],
            'options' => ['charset' => 'UTF8mb4','collate' => 'utf8mb4_bin','autoIncrement' => 1000],
        ];
        
        $statements = $adapter->createTableSql('tposts', $schema, $options);
        $this->assertEquals('b19d5510f223e5afc9ea69e5e0ae8a30', md5($statements[0]));

        if ($adapter->connection()->engine() === 'mysql') {
            $this->assertTrue($adapter->connection()->execute($statements[0]));
        }
    }

    public function testDescribeTable()
    {
        $adapter = new MysqlSchema('test');
        if ($adapter->connection()->engine() !== 'mysql') {
            $this->markTestSkipped('This test is for MySQL');
        }
        /**
         * Any slight changes should be investigated fully
         */
        $schema = $adapter->describe('tposts');
        
        /**
         * Difference is `body` text DEFAULT NULL, or `body` text both work on each other
         */
        $hash = md5(json_encode($schema));
        $mysql = 'ce3ecc5650694866b141495952429f5e';
        $mariadb = '772d3c533e138912606007b55defa1e8';
        $this->assertContains($hash, [$mysql,$mariadb]);
        $this->assertTrue($adapter->connection()->execute('DROP TABLE tposts'));
    }

    /**
     * This tests the new create table generator
     *
     * @return void
     */
    public function testCreateTableSqlBasic()
    {
        $adapter = new MysqlSchema('test');
        $schema = [
            'id' => 'integer',
            'title' => ['type' => 'string'],
            'description' => 'text',
            'created' => 'datetime',
            'modified' => 'datetime',
        ];
        $options = [
            'constraints' => [
                'primary' => ['type' => 'primary','column' => 'id'],
                'unique' => ['type' => 'unique', 'column' => ['title']],
            ],
            'options' => ['engine' => 'InnoDB','charset' => 'utf8','collate' => 'utf8_unicode_ci','autoIncrement' => 1000],
        ];
        $result = $adapter->createTableSql('tposts', $schema, $options);
       
        $this->assertEquals('e072e7ae67738ce47c8a9d6dde92d422', md5($result[0]));
        $this->assertEquals('f23839a1d5f204c328780d457e5ac4e8', md5($result[1]));

        if ($adapter->connection()->engine() === 'mysql') {
            foreach ($result as $statement) {
                $this->assertTrue($adapter->connection()->execute($statement));
            }
            $this->assertTrue($adapter->connection()->execute('DROP TABLE tposts'));
        }

        // Test composite primary keys
        $schema = [
            'article_id' => 'integer',
            'tag_id' => 'integer',
        ];
        $options = [
            'constraints' => [
                'primary' => ['type' => 'primary', 'column' => ['article_id','tag_id']],
                'unique' => ['type' => 'unique', 'column' => ['article_id','tag_id']],
            ],
        ];
        $result = $adapter->createTableSql('tarticles', $schema, $options);
        $this->assertEquals('22ef8b660ac2636593ee4e6585ce2393', md5($result[0]));
        if ($adapter->connection()->engine() === 'mysql') {
            foreach ($result as $statement) {
                $this->assertTrue($adapter->connection()->execute($statement));
            }
            $this->assertTrue($adapter->connection()->execute('DROP TABLE tarticles'));
        }
    }

    public function testCreateTableSqlIndex()
    {
        $adapter = new MysqlSchema('test');
        $schema = [
            'id' => 'integer',
            'title' => ['type' => 'string'],
            'code' => 'string',
            'description' => 'text',
            'created' => 'datetime',
            'modified' => 'datetime',
        ];
        $options = [
            'constraints' => [
                'primary' => ['type' => 'primary','column' => ['id']],
            ],
            'indexes' => [
                'title_u' => ['type' => 'unique','column' => ['code']], // test unique
                'title_idx' => ['type' => 'index','column' => ['title']],
                'title_ft' => ['type' => 'fulltext','column' => ['title']],
            ],
        ];
        $result = $adapter->createTableSql('tposts', $schema, $options);

        $this->assertEquals('2396c496250cc53cf4029753cfb7ebe7', md5($result[0]));

        if ($adapter->connection()->engine() === 'mysql') {
            foreach ($result as $statement) {
                $this->assertTrue($adapter->connection()->execute($statement));
            }
            $this->assertTrue($adapter->connection()->execute('DROP TABLE tposts'));
        }
    }

    public function testCreateTableSqlForeignKey()
    {
        $adapter = new MysqlSchema('test');

        $schema = [
            'id' => 'integer',
            'user_id' => 'integer',
            'name' => ['type' => 'string'],
        ];

        $options = [
            'constraints' => [
                'primary' => ['type' => 'primary','column' => ['id']],
                'unique' => ['type' => 'unique','column' => ['name']],
                'fk_users_id' => ['type' => 'foreign','column' => ['user_id'],'references' => ['users','id']],
            ],
        ];
        
        $result = $adapter->createTableSql('tarticles', $schema, $options);

        $this->assertEquals('a578d2dcc76c88ba3e1fd372bd365db9', md5($result[0]));

        $options = [
            'constraints' => [
                'primary' => ['type' => 'primary','column' => ['id']],
                'fk_users_id' => [
                    'type' => 'foreign',
                    'column' => ['user_id'],
                    'references' => ['users','id'],
                    'update' => 'cascade',
                    'delete' => 'restrict', ],
                
            ],
        ];

        $result = $adapter->createTableSql('tarticles', $schema, $options);

        $this->assertEquals('5714028332ba2d21d8889cc106a51f7a', md5($result[0]));

        // sanity check
        if ($adapter->connection()->engine() === 'mysql') {
            $schema = [
                'id' => 'integer',
                'name' => ['type' => 'string'],
            ];
    
            $options = [
                'constraints' => [
                    'primary' => ['type' => 'primary','column' => ['id']],
                ],
            ];

            $statements = $adapter->createTableSql('tusers', $schema, $options);

            $schema = [
                'id' => 'integer',
                'user_id' => 'integer',
                'name' => ['type' => 'string'],
            ];
    
            $options = [
                'constraints' => [
                    'primary' => ['type' => 'primary','column' => ['id']],
                    'fk_users_id' => ['type' => 'foreign','column' => ['user_id'],'references' => ['tusers','id']],
                ],
            ];

            $statements = array_merge($statements, $adapter->createTableSql('tarticles', $schema, $options));
            foreach ($statements as $statement) {
                $this->assertTrue($adapter->connection()->execute($statement));
            }

            $expected = [
                'type' => 'foreign',
                'column' => 'user_id',
                'references' => ['tusers','id'],
            ];
            $schema = $adapter->connection()->describe('tarticles');
            $this->assertEquals($expected, $schema['constraints']['fk_users_id']);

            $this->assertTrue($adapter->connection()->execute('DROP TABLE tarticles'));
            $this->assertTrue($adapter->connection()->execute('DROP TABLE tusers'));
        }
    }

    public function testAddColumn()
    {
        $adapter = new MysqlSchema('test');
        $expected = 'ALTER TABLE `apples` ADD COLUMN `colour` VARCHAR(255)';

        $result = $adapter->addColumn('apples', 'colour', 'string');
        $this->assertEquals($expected, $result);

        // Test limit
        $result = $adapter->addColumn('apples', 'colour', 'string', ['limit' => 40]);
        $expected = 'ALTER TABLE `apples` ADD COLUMN `colour` VARCHAR(40)';
        $this->assertEquals($expected, $result);

        $result = $adapter->addColumn('apples', 'qty', 'integer', ['limit' => 3]);
        $expected = 'ALTER TABLE `apples` ADD COLUMN `qty` INT(3)';
        $this->assertEquals($expected, $result);

        # # # TEST DEFAULTS

        // test default not null
        $expected = 'ALTER TABLE `apples` ADD COLUMN `colour` VARCHAR(255) NOT NULL DEFAULT \'foo\'';
        $result = $adapter->addColumn('apples', 'colour', 'string', ['default' => 'foo','null' => false]);
        $this->assertEquals($expected, $result);

        // test default null
        $expected = 'ALTER TABLE `apples` ADD COLUMN `colour` VARCHAR(255) DEFAULT \'foo\'';
        $result = $adapter->addColumn('apples', 'colour', 'string', ['default' => 'foo']);
        $this->assertEquals($expected, $result);

        // test null
        $expected = 'ALTER TABLE `apples` ADD COLUMN `colour` VARCHAR(255) DEFAULT NULL';
        $result = $adapter->addColumn('apples', 'colour', 'string', ['default' => '']);
        $this->assertEquals($expected, $result);

        // test not null (no default)
        $expected = 'ALTER TABLE `apples` ADD COLUMN `colour` VARCHAR(255) NOT NULL';
        $result = $adapter->addColumn('apples', 'colour', 'string', ['null' => false]);
        $this->assertEquals($expected, $result);

        // test precision
        $expected = 'ALTER TABLE `apples` ADD COLUMN `price` DECIMAL(7,0)';
        $result = $adapter->addColumn('apples', 'price', 'decimal', ['precision' => 7]);
        $this->assertEquals($expected, $result);

        $expected = 'ALTER TABLE `apples` ADD COLUMN `price` DECIMAL(8,2)';
        $result = $adapter->addColumn('apples', 'price', 'decimal', ['precision' => 8,'scale' => 2]);
        $this->assertEquals($expected, $result);

        if ($adapter->connection()->engine() === 'mysql') {
            $result = $adapter->addColumn('articles', 'category', 'string', ['default' => 'new','limit' => 40]);
            $this->assertTrue($adapter->connection()->execute($result));
        }

        # Test MySQL specials
        $expected = 'ALTER TABLE `apples` ADD COLUMN `description` TEXT';
        $result = $adapter->addColumn('apples', 'description', 'text');
        $this->assertEquals($expected, $result);

        $expected = 'ALTER TABLE `apples` ADD COLUMN `description` MEDIUMTEXT';
        $result = $adapter->addColumn('apples', 'description', 'text', ['limit' => 16777215]);
        $this->assertEquals($expected, $result);

        $expected = 'ALTER TABLE `apples` ADD COLUMN `description` LONGTEXT';
        $result = $adapter->addColumn('apples', 'description', 'text', ['limit' => 4294967295]);
        $this->assertEquals($expected, $result);

        # Test MySQL specials
    }

    public function testAddForeignKey()
    {
        $adapter = new MysqlSchema('test');
        $expected = 'ALTER TABLE `articles` ADD CONSTRAINT `fk_origin_12345` FOREIGN KEY (author_id) REFERENCES `users` (id)';
 
        $result = $adapter->addForeignKey('articles', 'fk_origin_12345', 'author_id', 'users', 'id');
        $this->assertEquals($expected, $result[0]);

        if ($adapter->connection()->engine() === 'mysql') {
            $this->assertTrue($adapter->connection()->execute($result[0]));
        }
    }

    public function testAddIndex()
    {
        $adapter = new MysqlSchema('test');
        $expected = 'CREATE INDEX `owner_name` ON `accounts` (owner_id)';
        $result = $adapter->addIndex('accounts', 'owner_id', 'owner_name');
        $this->assertEquals($expected, $result);

        $expected = 'CREATE INDEX `owner_name` ON `accounts` (owner_id, tenant_id)';
        $result = $adapter->addIndex('accounts', ['owner_id','tenant_id'], 'owner_name');
        $this->assertEquals($expected, $result);

        $expected = 'CREATE UNIQUE INDEX `owner_name` ON `accounts` (owner_id)';
        $result = $adapter->addIndex('accounts', 'owner_id', 'owner_name', ['unique' => true]);
        $this->assertEquals($expected, $result);

        // Small sanity check
        if ($adapter->connection()->engine() === 'mysql') {
            $result = $adapter->addIndex('articles', 'title', 'index_title');
            $this->assertTrue($adapter->connection()->execute($result));
        }

        $expected = 'CREATE FULLTEXT INDEX `idx_title` ON `topics` (title)';
        $result = $adapter->addIndex('topics', 'title', 'idx_title', ['type' => 'FULLTEXT']);
        $this->assertEquals($expected, $result);
    }
    public function testChangeColumn()
    {
        $adapter = new MysqlSchema('test');

        // ALTER TABLE `articles` MODIFY COLUMN `id` INT(15)

        $statements = $adapter->changeColumn('articles', 'id', 'integer', ['limit' => '15']);
        $this->assertEquals('e11c5824867c439975eb736d4742c3b4', md5(json_encode($statements)));

        # check for syntax errors
        if ($adapter->connection()->engine() === 'mysql') {
            foreach ($statements as $statement) {
                $this->assertTrue($adapter->connection()->execute($statement));
            }
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

    public function testSchemaValue()
    {
        $adapter = new MysqlSchema('test');
        $this->assertEquals('NULL', $adapter->schemaValue(null));
        $this->assertEquals(3, $adapter->schemaValue(3));
        $this->assertEquals("'foo'", $adapter->schemaValue('foo'));
        $this->assertEquals(1, $adapter->schemaValue(true));
        $this->assertEquals(0, $adapter->schemaValue(false));
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
        $this->assertInstanceOf(Connection::class, $adapter->connection());
    }

    public function testDatasource()
    {
        $adapter = new MysqlSchema('foo');
        $this->assertEquals('foo', $adapter->datasource());
        $adapter->datasource('bar');
        $this->assertEquals('bar', $adapter->datasource());
    }

    /**
     * Create a FOO table which next tests will depend upon
     */
    public function testCreateTable()
    {
        $adapter = new MysqlSchema('test');

        $schema = [
            'id' => ['type' => 'integer','autoIncrement' => true],
            'name' => ['type' => 'string','default' => ''],
            'description' => ['type' => 'text'],
            'created' => ['type' => 'datetime'],
            'modified' => ['type' => 'datetime'],
        ];
        $options = [
            'constraints' => ['primary' => ['type' => 'primary', 'column' => 'id']],
            'indexes' => [
                'idx_name' => ['type' => 'index', 'column' => 'name'],
            ]
        ];
        $statements = $adapter->createTableSql('foo', $schema, $options);
        $this->assertEquals('e5ec7835527bd04fe3f7ac8bfc480fca', md5(json_encode($statements)));

        if ($adapter->connection()->engine() !== 'mysql') {
            $this->markTestSkipped('This is test is for mysql');
        }
        foreach ($statements as $statement) {
            $this->assertTrue($adapter->connection()->execute($statement));
        }
    }

    public function testChangeAutoIncrementSql()
    {
        $adapter = new MysqlSchema('test');
        $expected = 'ALTER TABLE `foo` AUTO_INCREMENT = 1024';
        $result = $adapter->changeAutoIncrementSql('foo', 'id', 1024); # created in createTable
        $this->assertEquals($expected, $result);
        if ($adapter->connection()->engine() === 'mysql') {
            $this->assertTrue($adapter->connection()->execute($result));
        }
    }

    public function testDropTable()
    {
        $adapter = new MysqlSchema('test');
        $expected = 'DROP TABLE `foo`';
        $result = $adapter->dropTableSql('foo'); # created in createTable
        $this->assertEquals($expected, $result);
        $this->assertEquals('DROP TABLE IF EXISTS `foo`', $adapter->dropTableSql('foo', ['ifExists' => true]));
        if ($adapter->connection()->engine() === 'mysql') {
            $this->assertTrue($adapter->connection()->execute($result));
        }
    }

    public function testForeignKeyExists()
    {
        $stub = $this->getMock(MysqlSchema::class, ['foreignKeys']);
        // Configure the stub.

        $foreignKeys = [
            ['name' => 'fk_origin_e74ce85cbc','column' => 'author_name'],
        ];

        $stub->method('foreignKeys')
            ->willReturn($foreignKeys);
        # Check logic
        $this->assertTrue($stub->foreignKeyExists('books', ['column' => 'author_name']));
        $this->assertTrue($stub->foreignKeyExists('books', ['name' => 'fk_origin_e74ce85cbc']));
        $this->assertFalse($stub->foreignKeyExists('books', ['column' => 'id']));
        $this->assertFalse($stub->foreignKeyExists('books', ['name' => 'fk_origin_e75ce86cb2']));
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
        $sql = $adapter->addForeignKey('articles', 'fk_origin_12345', 'author_id', 'users', 'id', 'noAction', 'noAction');
 
        $this->assertTrue($adapter->connection()->execute($sql[0]));
       
        $expected = [
            'name' => 'fk_origin_12345',
            'table' => 'articles',
            'column' => 'author_id',
            'referencedTable' => 'users',
            'referencedColumn' => 'id',
            'update' => 'noAction',
            'delete' => 'noAction'
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
        $expected = [
            'name' => 'PRIMARY', // different on pgsql
            'column' => 'id',
            'type' => 'unique',
        ];
        $this->assertEquals($expected, $indexes[0]);

        $sql = $adapter->addIndex('articles', ['id','author_id'], 'test_multicolumn_index');
        $this->assertTrue($adapter->connection()->execute($sql));
        $indexes = $adapter->indexes('articles');
        $expected = [
            'name' => 'test_multicolumn_index',
            'column' => ['id','author_id'],
            'type' => 'index',
        ];
        $this->assertEquals($expected, $indexes[1]);
    }

    /**
     *
     *
     * @return void
     */
    public function testIndexFullText()
    {
        $adapter = new MysqlSchema('test');
        if ($adapter->connection()->engine() !== 'mysql') {
            $this->markTestSkipped('This test is for mysql');
        }
       
        $sql = $adapter->addIndex('articles', 'title', 'dummy_index', ['type' => 'fulltext']);
        $this->assertTrue($adapter->connection()->execute($sql));
       
        $indexes = $adapter->indexes('articles');
        $this->assertEquals('dummy_index', $indexes[1]['name']);
        $this->assertEquals('fulltext', $indexes[1]['type']);
    }

    public function testRemoveColumn()
    {
        $adapter = new MysqlSchema('test');
        $expected = 'ALTER TABLE `articles` DROP COLUMN `modified`';
        $result = $adapter->removeColumn('articles', 'modified');
        $this->assertEquals($expected, $result[0]);
    
        if ($adapter->connection()->engine() === 'mysql') {
            $this->assertTrue($adapter->connection()->execute($result[0]));
        }
    }

    public function testRemoveColumns()
    {
        $adapter = new MysqlSchema('test');
       
        $expected = "ALTER TABLE `articles`\nDROP COLUMN `created`,\nDROP COLUMN `modified`";
        $result = $adapter->removeColumns('articles', ['created','modified']);
        $this->assertEquals($expected, $result[0]);
 
        if ($adapter->connection()->engine() === 'mysql') {
            $this->assertTrue($adapter->connection()->execute($result[0]));
        }
    }

    /**
     * @depends testAddForeignKey
     */
    public function testRemoveForeignKey()
    {
        $adapter = new MysqlSchema('test');
        $expected = 'ALTER TABLE `articles` DROP FOREIGN KEY `fk_origin_12345`';
        $result = $adapter->removeForeignKey('articles', 'fk_origin_12345');
        $this->assertEquals($expected, $result[0]);

        if ($adapter->connection()->engine() === 'mysql') {
            $sql = $adapter->addForeignKey('articles', 'fk_origin_12345', 'author_id', 'users', 'id');
            $this->assertTrue($adapter->connection()->execute($sql[0]));
            $this->assertTrue($adapter->connection()->execute($result[0]));
        }
    }

    public function testRemoveIndex()
    {
        $adapter = new MysqlSchema('test');
        $expected = 'DROP INDEX `author_id` ON `articles`';
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

        $expected = 'ALTER TABLE `articles` CHANGE `author_id` `user_id` INT(11)';
        $result = $adapter->renameColumn('articles', 'author_id', 'user_id');
        $this->assertEquals($expected, $result[0]);
        $this->assertTrue($adapter->connection()->execute($result[0]));

        $expected = 'ALTER TABLE `deals` CHANGE `amount` `deal_amount` DECIMAL(15,2)';
        $result = $adapter->renameColumn('deals', 'amount', 'deal_amount');
        $this->assertEquals($expected, $result[0]);
        $this->assertTrue($adapter->connection()->execute($result[0]));
    }

    /**
     * @xxxdepends testAddIndex
     */
    public function testRenameIndex()
    {
        $adapter = new MysqlSchema('test');
        if ($adapter->connection()->engine() !== 'mysql') {
            $this->markTestSkipped('Cant test this without MySQL');
        }

        $sql = $adapter->addIndex('articles', 'title', 'search_title');
        $this->assertTrue($adapter->connection()->execute($sql));

        $expected = [
            'CREATE INDEX `title_search` ON `articles` (title)',
            'DROP INDEX `search_title` ON `articles`',
        ];
        $statements = $adapter->renameIndex('articles', 'search_title', 'title_search');

        $this->assertSame($expected, $statements);
        foreach ($statements as $statement) {
            $this->assertTrue($adapter->connection()->execute($statement));
        }
    }
    
    public function testRenameTable()
    {
        $adapter = new MysqlSchema('test');
        $expected = 'RENAME TABLE `articles` TO `artikel`';
        $result = $adapter->renameTable('articles', 'artikel');
        $this->assertEquals($expected, $result);
        if ($adapter->connection()->engine() === 'mysql') {
            $this->assertTrue($adapter->connection()->execute($result));
            $result = $adapter->renameTable('artikel', 'articles');
            $this->assertTrue($adapter->connection()->execute($result)); // rename back for fixture manager
        }
    }

    public function testShowCreateTable()
    {
        $adapter = new MysqlSchema('test');
        if ($adapter->connection()->engine() !== 'mysql') {
            $this->markTestSkipped('This test is for mysql');
        }

        $result = $adapter->showCreateTable('articles');
  
        $this->assertStringContainsString('CREATE TABLE `articles` (', $result);
        $this->assertStringContainsString('`id` int(11) NOT NULL AUTO_INCREMENT,', $result);
        $this->assertStringContainsString('`author_id` int(11) DEFAULT NULL,', $result);
        $this->assertStringContainsString('`title` varchar(255) NOT NULL,', $result);
        $this->assertMatchesRegularExpression('/`body` text(,| DEFAULT NULL,)/', $result); // MariaDB/MySQL Difference
        $this->assertStringContainsString('`created` datetime DEFAULT NULL', $result);
        $this->assertStringContainsString('`modified` datetime DEFAULT NULL', $result);
        $this->assertStringContainsString('PRIMARY KEY (`id`)', $result);
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
        $this->assertEquals(['articles','deals','posts','users'], $adapter->tables());
    }
}
