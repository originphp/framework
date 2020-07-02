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

namespace Origin\Test\Model\Schema;

use Origin\Model\Connection;
use InvalidArgumentException;
use Origin\TestSuite\OriginTestCase;
use Origin\Model\Schema\SqliteSchema;

class SqliteSchemaTest extends OriginTestCase
{
    protected $fixtures = ['Origin.Post','Origin.User','Origin.Article','Origin.Deal'];

    public function testCreateTableColumns()
    {
        
        // todo unsigned
        $adapter = new SqliteSchema('test');

        $schema = [
            'f0' => ['type' => 'integer','autoIncrement' => true,'comment' => 'This will be primary key'],
            'f1' => ['type' => 'string','limit' => 80,'null' => false],
            'f2' => ['type' => 'string', 'fixed' => true,'default' => 'default string'],
            'f3' => ['type' => 'text','default' => 'some default text'],
            'f4' => ['type' => 'integer','default' => 100],
            'f5' => ['type' => 'float'], # on pgsql float is bytes
            'f6' => ['type' => 'decimal','precision' => 10,'scale' => 3],
            'f7' => 'datetime',
            'f8' => 'time',
            'f9' => 'timestamp',
            'f10' => 'date',
            'f11' => 'binary',
            'f12' => 'boolean',
            'f13' => ['type' => 'bigint','default' => 10000,'null' => false], // test bigint/default/null=false
            'f14' => ['type' => 'string','default' => 'default value'],
            'f15' => ['type' => 'integer','limit' => 123,'default' => 12345789], // test integer limit is not set
        ];
        /**
         * These options are needed for the describe test, since we will use the same table.
         */
        $options = [
            'constraints' => [
                'p' => ['type' => 'primary','column' => ['f0']],
                'u1' => ['type' => 'unique','column' => ['f1']],
            ],
            'indexes' => [
                'u2' => ['type' => 'index','column' => ['f2']], // we need this for the next test
            ],
            'options' => ['autoIncrement' => 1000],
        ];
        $statements = $adapter->createTableSql('tposts', $schema, $options);
   
        $this->assertEquals('986833f9f7b31d0266ed7256f130077d', md5($statements[0]));
        $this->assertEquals('CREATE INDEX "u2" ON "tposts" (f2)', $statements[1]);
     
        if ($adapter->connection()->engine() === 'sqlite') {
            $this->assertTrue($adapter->connection()->execute($statements[0]));
            $this->assertTrue($adapter->connection()->execute($statements[1]));
        }
    }

    // not getting correct constraint
    public function testDescribeTable()
    {
        $adapter = new SqliteSchema('test');

        if ($adapter->connection()->engine() !== 'sqlite') {
            $this->markTestSkipped('This test is for Sqlite');
        }
        /**
         * Any slight changes should be investigated fully
         */
        $schema = $adapter->describe('tposts');
      
        $this->assertEquals('7e98d46f271dd9b1d5d0e3541f9c1b7a', md5(json_encode($schema)));
        $this->assertTrue($adapter->connection()->execute('DROP TABLE tposts'));
    }

    /**
         * This tests the new create table generator
         *
         * @return void
         */
    public function testCreateTableSqlBasic()
    {
        $adapter = new SqliteSchema('test');
        $schema = [
            'id' => ['type' => 'integer','autoIncrement' => true],
            'title' => ['type' => 'string', 'null' => false],
            'category' => ['type' => 'string', 'null' => true],
            'status' => ['type' => 'string', 'null' => false, 'default' => 'new'],
            'description' => 'text',
            'created' => 'datetime',
            'modified' => 'datetime',
        ];
        $options = [
            'constraints' => [
                'primary' => ['type' => 'primary','column' => 'id'],
                'unique' => ['type' => 'unique', 'column' => 'title'],
            ],
            'options' => ['autoIncrement' => 1000],
        ];
        $result = $adapter->createTableSql('tposts', $schema, $options);

        $this->assertEquals('6abdb182ca0d0b8089cbab6ff063907f', md5($result[0]));
   
        if ($adapter->connection()->engine() === 'sqlite') {
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

        $this->assertEquals('7c4830eba8bfa1f521e3297d76f4be90', md5($result[0]));
        if ($adapter->connection()->engine() === 'sqlite') {
            $adapter->connection()->execute($adapter->dropTableSql('tarticles', ['ifExists' => true]));

            foreach ($result as $statement) {
                $this->assertTrue($adapter->connection()->execute($statement));
            }
            $this->assertTrue($adapter->connection()->execute('DROP TABLE tarticles'));
        }
    }

    public function testCreateTableSqlIndex()
    {
        $adapter = new SqliteSchema('test');
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
                'title_u' => ['type' => 'unique','column' => ['code']],
                'title_idx' => ['type' => 'index','column' => ['title']],
                'title_ft' => ['type' => 'fulltext','column' => ['title']], // does not exist on pgsql, but test we normal index
            ],
        ];
        $result = $adapter->createTableSql('tposts', $schema, $options);
      
        $this->assertEquals('d0b8f36fd14522f03292b598a21fe5e3', md5($result[0]));
        $this->assertStringContainsString('CREATE UNIQUE INDEX "title_u" ON "tposts" (code)', $result[1]);
        $this->assertStringContainsString('CREATE INDEX "title_idx" ON "tposts" (title)', $result[2]);
        $this->assertStringContainsString('CREATE INDEX "title_ft" ON "tposts" (title)', $result[3]);

        if ($adapter->connection()->engine() === 'sqlite') {
            foreach ($result as $statement) {
                $this->assertTrue($adapter->connection()->execute($statement));
            }
            $this->assertTrue($adapter->connection()->execute('DROP TABLE tposts'));
        }
    }

    public function testCreateTableSqlForeignKey()
    {
        $adapter = new SqliteSchema('test');

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
        $this->assertEquals('3277f3b5ff41e87f93a0899e1eae8c2f', md5($result[0]));

        $options = [
            'constraints' => [
                'primary' => ['type' => 'primary','column' => ['id']],
                'fk_users_id' => [
                    'type' => 'foreign',
                    'column' => ['user_id'],
                    'references' => ['users','id'],
                    'update' => 'cascade',
                    'delete' => 'cascade', ],
                
            ],
        ];

        $result = $adapter->createTableSql('tarticles', $schema, $options);
        $this->assertEquals('2f53804c0b56650ffc26270e23127916', md5($result[0]));

        // sanity check
        if ($adapter->connection()->engine() === 'sqlite') {
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
            $schema = $adapter->connection()->describe('tarticles');

            $expected = [
                'type' => 'foreign',
                'column' => 'user_id',
                'references' => ['tusers','id'],
            ];
           
            $this->assertEquals($expected, $schema['constraints']['fk_97a33924']);

            $this->assertTrue($adapter->connection()->execute('DROP TABLE tarticles'));
            $this->assertTrue($adapter->connection()->execute('DROP TABLE tusers'));
        }
    }

    public function testAddColumn()
    {
        $adapter = new SqliteSchema('test');
        $expected = 'ALTER TABLE "apples" ADD COLUMN "colour" VARCHAR(255)';
        
        $result = $adapter->addColumn('apples', 'colour', 'string');
        $this->assertEquals($expected, $result);
        
        // Test limit
        $result = $adapter->addColumn('apples', 'colour', 'string', ['limit' => 40]);
        $expected = 'ALTER TABLE "apples" ADD COLUMN "colour" VARCHAR(40)';
        $this->assertEquals($expected, $result);

        // sqlite does not have limit for integer
        $result = $adapter->addColumn('apples', 'qty', 'integer', ['limit' => 3]);
        $expected = 'ALTER TABLE "apples" ADD COLUMN "qty" INTEGER';
        $this->assertEquals($expected, $result);

        # # # TEST DEFAULTS
        
        // test default not null
        $expected = 'ALTER TABLE "apples" ADD COLUMN "colour" VARCHAR(255) NOT NULL DEFAULT \'foo\'';
        $result = $adapter->addColumn('apples', 'colour', 'string', ['default' => 'foo','null' => false]);
        $this->assertEquals($expected, $result);

        // test default null
        $expected = 'ALTER TABLE "apples" ADD COLUMN "colour" VARCHAR(255) DEFAULT \'foo\'';
        $result = $adapter->addColumn('apples', 'colour', 'string', ['default' => 'foo']);
        $this->assertEquals($expected, $result);

        // test null
        $expected = 'ALTER TABLE "apples" ADD COLUMN "colour" VARCHAR(255) DEFAULT NULL';
        $result = $adapter->addColumn('apples', 'colour', 'string', ['default' => '']);
        $this->assertEquals($expected, $result);

        // test not null (no default)
        $expected = 'ALTER TABLE "apples" ADD COLUMN "colour" VARCHAR(255) NOT NULL';
        $result = $adapter->addColumn('apples', 'colour', 'string', ['null' => false]);
        $this->assertEquals($expected, $result);
        
        // test precision
        $expected = 'ALTER TABLE "apples" ADD COLUMN "price" DECIMAL(7,0)';
        $result = $adapter->addColumn('apples', 'price', 'decimal', ['precision' => 7]);
        $this->assertEquals($expected, $result);

        $expected = 'ALTER TABLE "apples" ADD COLUMN "price" DECIMAL(8,2)';
        $result = $adapter->addColumn('apples', 'price', 'decimal', ['precision' => 8,'scale' => 2]);
        $this->assertEquals($expected, $result);

        if ($adapter->connection()->engine() === 'sqlite') {
            $result = $adapter->addColumn('articles', 'category', 'string', ['default' => 'new','limit' => 40]);
            $this->assertTrue($adapter->connection()->execute($result));
        }
    }

    public function testAddForeignKey()
    {
        $adapter = new SqliteSchema('test');
        if ($adapter->connection()->engine() !== 'sqlite') {
            $this->markTestSkipped();
        }
       
        $statements = $adapter->addForeignKey('articles', 'fk_12345', 'author_id', 'users', 'id');
        $this->assertEquals('2d99dd3486675e9a6df37cbabee69ddc', md5(json_encode($statements)));

        foreach ($statements as $statement) {
            $this->assertTrue($adapter->connection()->execute($statement));
        }
    }

    public function testAddIndex()
    {
        $adapter = new SqliteSchema('test');
        $expected = 'CREATE INDEX "owner_name" ON "accounts" (owner_id)';
        $result = $adapter->addIndex('accounts', 'owner_id', 'owner_name');
        $this->assertEquals($expected, $result);

        $expected = 'CREATE INDEX "owner_name" ON "accounts" (owner_id, tenant_id)';
        $result = $adapter->addIndex('accounts', ['owner_id','tenant_id'], 'owner_name');
        $this->assertEquals($expected, $result);

        $expected = 'CREATE UNIQUE INDEX "owner_name" ON "accounts" (owner_id)';
        $result = $adapter->addIndex('accounts', 'owner_id', 'owner_name', ['unique' => true]);
        $this->assertEquals($expected, $result);

        // Small sanity check
        if ($adapter->connection()->engine() === 'sqlite') {
            $result = $adapter->addIndex('articles', 'title', 'index_title');
            $this->assertTrue($adapter->connection()->execute($result));
        }
        // The following command wont work on PGSQL, just testing the type
        $expected = 'CREATE PREFIX INDEX "idx_title" ON "topics" (title)';
        $result = $adapter->addIndex('topics', 'title', 'idx_title', ['type' => 'prefix']);
        $this->assertEquals($expected, $result);
    }

    public function testChangeColumn()
    {
        $adapter = new SqliteSchema('test');
        if ($adapter->connection()->engine() != 'sqlite') {
            $this->markTestSkipped('Requires sqlite');
        }
       
        $statements = $adapter->changeColumn('articles', 'title', 'string', ['limit' => '15']);
        $this->assertEquals('4fbafb0c1ae18ec5ae8b0933c5ee5ecd', md5(json_encode($statements)));

        foreach ($statements as $statement) {
            $this->assertTrue($adapter->connection()->execute($statement));
        }
        $meta = $adapter->describe('articles')['columns'];
        $this->assertEquals('15', $meta['title']['limit']);
    }

    public function testColumnExists()
    {
        $adapter = new SqliteSchema('test');
        if ($adapter->connection()->engine() !== 'sqlite') {
            $this->markTestSkipped('This is test is for pgsql');
        }
       
        $this->assertTrue($adapter->columnExists('posts', 'title'));
        $this->assertFalse($adapter->columnExists('posts', 'titles'));
    }

    public function testSchemaValue()
    {
        $adapter = new SqliteSchema('test');
        $this->assertEquals('NULL', $adapter->schemaValue(null));
        $this->assertEquals(3, $adapter->schemaValue(3));
        $this->assertEquals("'foo'", $adapter->schemaValue('foo'));
        $this->assertEquals(1, $adapter->schemaValue(true));
        $this->assertEquals(0, $adapter->schemaValue(false));
    }

    public function testColumns()
    {
        $adapter = new SqliteSchema('test');
        if ($adapter->connection()->engine() !== 'sqlite') {
            $this->markTestSkipped('This is test is for pgsql');
        }
        $expected = ['id','title','body','published','created','modified'];
        $result = $adapter->columns('posts');
        $this->assertEquals($expected, $result);
    }

    public function testConnection()
    {
        $adapter = new SqliteSchema('test');
        $this->assertInstanceOf(Connection::class, $adapter->connection());
    }

    public function testDatasource()
    {
        $adapter = new SqliteSchema('foo');
        $this->assertEquals('foo', $adapter->datasource());
        $adapter->datasource('bar');
        $this->assertEquals('bar', $adapter->datasource());
    }

    /**
     * Create a FOO table which next tests will depend upon
     */
    public function testCreateTable()
    {
        $adapter = new SqliteSchema('test');
        if ($adapter->connection()->engine() !== 'sqlite') {
            $this->markTestSkipped('This is test is for pgsql');
        }

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
        $result = $adapter->createTableSql('foo', $schema, $options);
        $this->assertEquals('2066db80f80aded3e9f108be2ec987b3', md5(json_encode($result)));
        
        foreach ($result as $statement) {
            $this->assertTrue($adapter->connection()->execute($statement));
        }
    }

    public function testChangeAutoIncrementSql()
    {
        $adapter = new SqliteSchema('test');
        $expected = 'UPDATE SQLITE_SEQUENCE SET seq = 1024 WHERE name = "foo"';
        $result = $adapter->changeAutoIncrementSql('foo', 'id', 1024); # created in createTable
        $this->assertEquals($expected, $result);
        if ($adapter->connection()->engine() === 'sqlite') {
            $this->assertTrue($adapter->connection()->execute($result));
        }
    }

    public function testDropTable()
    {
        $adapter = new SqliteSchema('test');
        $expected = 'DROP TABLE "foo"';
        $result = $adapter->dropTableSql('foo'); # created in createTable
        $this->assertEquals($expected, $result);
        $this->assertEquals('DROP TABLE IF EXISTS "foo"', $adapter->dropTableSql('foo', ['ifExists' => true]));
        if ($adapter->connection()->engine() === 'sqlite') {
            $this->assertTrue($adapter->connection()->execute($result));
        }
    }

    public function testForeignKeyExists()
    {
        $stub = $this->getMock(SqliteSchema::class, ['foreignKeys']);
        // Configure the stub.

        $foreignKeys = [
            ['name' => 'fk_e74ce85cbc','column' => 'author_name'],
        ];

        $stub->method('foreignKeys')
            ->willReturn($foreignKeys);
        # Check logic
        $this->assertTrue($stub->foreignKeyExists('books', ['column' => 'author_name']));
        $this->assertTrue($stub->foreignKeyExists('books', ['name' => 'fk_e74ce85cbc']));
        $this->assertFalse($stub->foreignKeyExists('books', ['column' => 'id']));
        $this->assertFalse($stub->foreignKeyExists('books', ['name' => 'fk_e75ce86cb2']));
    }

    /**
     * @depends testAddForeignKey
     */
    public function testForeignKeys()
    {
        $adapter = new SqliteSchema('test');
        if ($adapter->connection()->engine() !== 'sqlite') {
            $this->markTestSkipped('This test is for sqlite');
        }
        $statements = $adapter->addForeignKey('articles', 'fk_12345', 'author_id', 'users', 'id');
      
        foreach ($statements as $statement) {
            $this->assertTrue($adapter->connection()->execute($statement));
        }

        // In Sqlite name is generated when retrieving to avoid  having to parse data.
        $expected = [
            'name' => 'fk_5e445c0c', //
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
        $adapter = new SqliteSchema('test');
        if ($adapter->connection()->engine() !== 'sqlite') {
            $this->markTestSkipped('This test is for sqlite');
        }
        $indexes = $adapter->indexes('articles');

        $this->assertEmpty($indexes);

        $sql = $adapter->addIndex('articles', 'id', 'single_column_index');
        $this->assertTrue($adapter->connection()->execute($sql));
        $indexes = $adapter->indexes('articles');
        $expected = [
            'name' => 'single_column_index',
            'column' => ['id'],
            'type' => 'index',
        ];
 
        $this->assertEquals($expected, $indexes[0]);
       
        // Tests multi column index  + normal index type
        $sql = $adapter->addIndex('articles', ['id','author_id'], 'test_multicolumn_index');
        $this->assertTrue($adapter->connection()->execute($sql));
        $indexes = $adapter->indexes('articles');
    
        $expected = [
            'name' => 'test_multicolumn_index',
            'column' => ['id','author_id'],
            'type' => 'index',
        ];
        $this->assertEquals($expected, $indexes[0]); // this is correct, latest top
    }

    public function testRemoveColumn()
    {
        $adapter = new SqliteSchema('test');
      
        if ($adapter->connection()->engine() !== 'sqlite') {
            $this->markTestSkipped();
        }

        $statements = $adapter->removeColumn('articles', 'modified');
      
        $this->assertEquals('1e9ebf5ffef547f150ed11aa5b8b0aab', md5(json_encode($statements)));
        foreach ($statements as $statement) {
            $this->assertTrue($adapter->connection()->execute($statement));
        }
    }

    public function testRemoveColumns()
    {
        $adapter = new SqliteSchema('test');

        if ($adapter->connection()->engine() !== 'sqlite') {
            $this->markTestSkipped();
        }

        $statements = $adapter->removeColumns('articles', ['created','modified']);

        $this->assertEquals('10f6fb602326d3483f1fa8229c4fc8a6', md5(json_encode($statements)));

        foreach ($statements as $statement) {
            $this->assertTrue($adapter->connection()->execute($statement));
        }
    }

    /**
     * @depends testAddForeignKey
     */
    public function testRemoveForeignKey()
    {
        $adapter = new SqliteSchema('test');
        if ($adapter->connection()->engine() != 'sqlite') {
            $this->markTestSkipped('Skipping');
        }

        $statements = $adapter->addForeignKey('articles', 'fk_12345', 'author_id', 'users', 'id');
     
        foreach ($statements as $statement) {
            $this->assertTrue($adapter->connection()->execute($statement));
        }

        $statements = $adapter->removeForeignKey('articles', 'fk_5e445c0c');

        $this->assertEquals('68002eb7e8b1cf066121e066b53e8bfe', md5(json_encode($statements)));
        foreach ($statements as $statement) {
            $this->assertTrue($adapter->connection()->execute($statement));
        }

        $this->expectException(InvalidArgumentException::class);
        $adapter->removeForeignKey('articles', 'foo');
    }

    public function testRemoveIndex()
    {
        $adapter = new SqliteSchema('test');
        $expected = 'DROP INDEX "author_id"'; // Different on pgsql no table name used
        $result = $adapter->removeIndex('articles', 'author_id');
        $this->assertEquals($expected, $result);
    }
    
    public function testRenameColumn()
    {
        $adapter = new SqliteSchema('test');
        /**
         * for pgsql comptability with older versions it needs to run some statements
         * this test for pgsql pgsql is a simple command
         */
        if ($adapter->connection()->engine() !== 'sqlite') {
            $this->markTestSkipped('This test is for sqlite');
        }
    
        $statements = $adapter->renameColumn('articles', 'author_id', 'user_id');
    
        $this->assertEquals('6a874657926d2ccdc3ce679c589266f5', md5(json_encode($statements)));

        foreach ($statements as $statement) {
            $this->assertTrue($adapter->connection()->execute($statement));
        }

        $schema = $adapter->describe('articles');
        $this->assertArrayHasKey('user_id', $schema['columns']);
    }

    public function testRenameIndex()
    {
        $adapter = new SqliteSchema('test');
   
        if ($adapter->connection()->engine() !== 'sqlite') {
            $this->markTestSkipped();
        }

        $sql = $adapter->addIndex('articles', 'title', 'search_title');
        $this->assertTrue($adapter->connection()->execute($sql));
        
        $statements = $adapter->renameIndex('articles', 'search_title', 'title_search');
 
        $this->assertEquals('fa8aae5397107b1cd933b8f7301d1912', md5(json_encode($statements)));
        foreach ($statements as $statement) {
            $this->assertTrue($adapter->connection()->execute($statement));
        }
    }
    
    public function testRenameTable()
    {
        $adapter = new SqliteSchema('test');
        $expected = 'ALTER TABLE "articles" RENAME TO "artikel"'; // different on pgsql
        $result = $adapter->renameTable('articles', 'artikel');
        $this->assertEquals($expected, $result);
        if ($adapter->connection()->engine() === 'sqlite') {
            $this->assertTrue($adapter->connection()->execute($result));
            $result = $adapter->renameTable('artikel', 'articles');
            $this->assertTrue($adapter->connection()->execute($result)); // rename back for fixture manager
        }
    }
    
    public function testShowCreateTable()
    {
        $adapter = new SqliteSchema('test');
        if ($adapter->connection()->engine() !== 'sqlite') {
            $this->markTestSkipped('This test is for sqlite');
        }

        $result = $adapter->showCreateTable('articles');
        $this->assertEquals('0f64f57b1ab596399b50276e30d2d748', md5($result));
    }

    public function testTableExists()
    {
        $adapter = new SqliteSchema('test');
        if ($adapter->connection()->engine() !== 'sqlite') {
            $this->markTestSkipped('This test is for sqlite');
        }
        $this->assertTrue($adapter->tableExists('articles'));
        $this->assertFalse($adapter->tableExists('foos'));
    }

    public function testTables()
    {
        $adapter = new SqliteSchema('test');
        if ($adapter->connection()->engine() !== 'sqlite') {
            $this->markTestSkipped('This test is for sqlite');
        }
       
        $tables = $adapter->tables();
   
        $this->assertEquals(['articles','deals','posts','users'], $tables); // assert equals crashing phpunit
    }
}
