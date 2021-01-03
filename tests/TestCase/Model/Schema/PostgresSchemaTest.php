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
use Origin\TestSuite\OriginTestCase;
use Origin\Model\Schema\PostgresSchema;

class PostgresSchemaTest extends OriginTestCase
{
    protected $fixtures = ['Origin.Post', 'Origin.User', 'Origin.Article', 'Origin.Deal'];

    public function testCreateTableColumns()
    {

        // todo unsigned
        $adapter = new PostgresSchema('test');
        $schema = [
            'f0' => ['type' => 'integer', 'autoIncrement' => true, 'comment' => 'This will be primary key'],
            'f1' => ['type' => 'string', 'limit' => 80, 'null' => false],
            'f2' => ['type' => 'string', 'fixed' => true, 'default' => 'default string'],
            'f3' => ['type' => 'text', 'default' => 'some default text'],
            'f4' => ['type' => 'integer', 'default' => 100],
            'f5' => ['type' => 'float'], # on pgsql float is bytes
            'f6' => ['type' => 'decimal', 'precision' => 10, 'scale' => 3],
            'f7' => 'datetime',
            'f8' => 'time',
            'f9' => 'timestamp',
            'f10' => 'date',
            'f11' => 'binary',
            'f12' => 'boolean',
            'f13' => ['type' => 'bigint', 'default' => 10000, 'null' => false], // test bigint/default/null=false
            'f14' => ['type' => 'string', 'default' => 'default value'],
            'f15' => ['type' => 'integer', 'limit' => 123, 'default' => 12345789], // test integer limit is not set
        ];
        /**
         * These options are needed for the describe test, since we will use the same table.
         */
        $options = [
            'constraints' => [
                'p' => ['type' => 'primary', 'column' => ['f0']],
                'u1' => ['type' => 'unique', 'column' => ['f1']],
            ],
            'indexes' => [
                'u2' => ['type' => 'index', 'column' => ['f2']], // we need this for the next test
            ],
            'options' => ['autoIncrement' => 1000],
        ];
        $statements = $adapter->createTableSql('tposts', $schema, $options);

        $this->assertEquals('748bcf000f5716f447166950340b9575', md5($statements[0]));
        $this->assertEquals('CREATE INDEX "u2" ON "tposts" (f2)', $statements[1]);
        $this->assertEquals('COMMENT ON COLUMN "tposts"."f0" IS \'This will be primary key\'', $statements[2]);

        if ($adapter->connection()->engine() === 'postgres') {
            $this->assertTrue($adapter->connection()->execute($statements[0]));
            $this->assertTrue($adapter->connection()->execute($statements[1]));
        }
    }

    // not getting correct constraint
    public function testDescribeTable()
    {
        $adapter = new PostgresSchema('test');
        if ($adapter->connection()->engine() !== 'postgres') {
            $this->markTestSkipped('This test is for PgSQL');
        }
        /**
         * Any slight changes should be investigated fully
         */
        $schema = $adapter->describe('tposts');

        $this->assertEquals('b4159faa9acaa065f45fb4f2ce0307af', md5(json_encode($schema)));
        $this->assertTrue($adapter->connection()->execute('DROP TABLE tposts'));
    }

    /**
     * This tests the new create table generator
     *
     * @return void
     */
    public function testCreateTableSqlBasic()
    {
        $adapter = new PostgresSchema('test');
        $schema = [
            'id' => ['type' => 'integer', 'autoIncrement' => true],
            'title' => ['type' => 'string'],
            'description' => 'text',
            'created' => 'datetime',
            'modified' => 'datetime',
        ];
        $options = [
            'constraints' => [
                'primary' => ['type' => 'primary', 'column' => 'id'],
                'unique' => ['type' => 'unique', 'column' => 'title'],
            ],
            'options' => ['autoIncrement' => 1000],
        ];
        $result = $adapter->createTableSql('tposts', $schema, $options);

        $this->assertEquals('d406a607d78e19c3a5490ee66890799a', md5($result[0]));
        $this->assertEquals('391876f15125755e54eca9cdcbb1d1a9', md5($result[1]));
        if ($adapter->connection()->engine() === 'postgres') {
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
                'primary' => ['type' => 'primary', 'column' => ['article_id', 'tag_id']],
                'unique' => ['type' => 'unique', 'column' => ['article_id', 'tag_id']],
            ],
        ];
        $result = $adapter->createTableSql('tarticles', $schema, $options);

        $this->assertEquals('11c6420036d251d2a7f9c9df9cb2971f', md5($result[0]));
        if ($adapter->connection()->engine() === 'postgres') {
            foreach ($result as $statement) {
                $this->assertTrue($adapter->connection()->execute($statement));
            }
            $this->assertTrue($adapter->connection()->execute('DROP TABLE tarticles'));
        }
    }

    public function testCreateTableSqlIndex()
    {
        $adapter = new PostgresSchema('test');
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
                'primary' => ['type' => 'primary', 'column' => ['id']],
            ],
            'indexes' => [
                'title_u' => ['type' => 'unique', 'column' => ['code']],
                'title_idx' => ['type' => 'index', 'column' => ['title']],
                'title_ft' => ['type' => 'fulltext', 'column' => ['title']], // does not exist on pgsql, but test we normal index
            ],
        ];
        $result = $adapter->createTableSql('tposts', $schema, $options);

        $this->assertEquals('4d6bbdd7f570bb892c3a484a1621a3f6', md5($result[0]));
        $this->assertStringContainsString('CREATE UNIQUE INDEX "title_u" ON "tposts" (code)', $result[1]);
        $this->assertStringContainsString('CREATE INDEX "title_idx" ON "tposts" (title)', $result[2]);
        $this->assertStringContainsString('CREATE INDEX "title_ft" ON "tposts" (title)', $result[3]);

        if ($adapter->connection()->engine() === 'postgres') {
            foreach ($result as $statement) {
                $this->assertTrue($adapter->connection()->execute($statement));
            }
            $this->assertTrue($adapter->connection()->execute('DROP TABLE tposts'));
        }
    }

    public function testCreateTableSqlForeignKey()
    {
        $adapter = new PostgresSchema('test');

        $schema = [
            'id' => 'integer',
            'user_id' => 'integer',
            'name' => ['type' => 'string'],
        ];

        $options = [
            'constraints' => [
                'primary' => ['type' => 'primary', 'column' => ['id']],
                'unique' => ['type' => 'unique', 'column' => ['name']],
                'fk_users_id' => ['type' => 'foreign', 'column' => ['user_id'], 'references' => ['users', 'id']],
            ],
        ];

        $result = $adapter->createTableSql('tarticles', $schema, $options);

        $this->assertEquals('24fa96935e3c34613596bc7d9a29f93d', md5($result[0]));

        $options = [
            'constraints' => [
                'primary' => ['type' => 'primary', 'column' => ['id']],
                'fk_users_id' => [
                    'type' => 'foreign',
                    'column' => ['user_id'],
                    'references' => ['users', 'id'],
                    'update' => 'cascade',
                    'delete' => 'cascade',
                ],

            ],
        ];

        $result = $adapter->createTableSql('tarticles', $schema, $options);
        $this->assertEquals('38b774843bdba657fbb52ff58ee9dc14', md5($result[0]));

        // sanity check
        if ($adapter->connection()->engine() === 'postgres') {
            $schema = [
                'id' => 'integer',
                'name' => ['type' => 'string'],
            ];

            $options = [
                'constraints' => [
                    'primary' => ['type' => 'primary', 'column' => ['id']],
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
                    'primary' => ['type' => 'primary', 'column' => ['id']],
                    'fk_users_id' => ['type' => 'foreign', 'column' => ['user_id'], 'references' => ['tusers', 'id']],
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
                'references' => ['tusers', 'id'],
            ];
            $this->assertEquals($expected, $schema['constraints']['fk_users_id']);

            $this->assertTrue($adapter->connection()->execute('DROP TABLE tarticles'));
            $this->assertTrue($adapter->connection()->execute('DROP TABLE tusers'));
        }
    }

    public function testAddColumn()
    {
        $adapter = new PostgresSchema('test');
        $expected = 'ALTER TABLE "apples" ADD COLUMN "colour" VARCHAR(255)';

        $result = $adapter->addColumn('apples', 'colour', 'string');
        $this->assertEquals($expected, $result);

        // Test limit
        $result = $adapter->addColumn('apples', 'colour', 'string', ['limit' => 40]);
        $expected = 'ALTER TABLE "apples" ADD COLUMN "colour" VARCHAR(40)';
        $this->assertEquals($expected, $result);

        $result = $adapter->addColumn('apples', 'qty', 'integer', ['limit' => 3]);
        $expected = 'ALTER TABLE "apples" ADD COLUMN "qty" INTEGER'; # Different on PGSQL
        $this->assertEquals($expected, $result);

        # # # TEST DEFAULTS

        // test default not null
        $expected = 'ALTER TABLE "apples" ADD COLUMN "colour" VARCHAR(255) NOT NULL DEFAULT \'foo\'';
        $result = $adapter->addColumn('apples', 'colour', 'string', ['default' => 'foo', 'null' => false]);
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
        $result = $adapter->addColumn('apples', 'price', 'decimal', ['precision' => 8, 'scale' => 2]);
        $this->assertEquals($expected, $result);

        if ($adapter->connection()->engine() === 'postgres') {
            $result = $adapter->addColumn('articles', 'category', 'string', ['default' => 'new', 'limit' => 40]);
            $this->assertTrue($adapter->connection()->execute($result));
        }
    }

    public function testAddForeignKey()
    {
        $adapter = new PostgresSchema('test');
        $expected = 'ALTER TABLE "articles" ADD CONSTRAINT "fk_origin_12345" FOREIGN KEY (author_id) REFERENCES "users" (id) DEFERRABLE INITIALLY IMMEDIATE';
        $result = $adapter->addForeignKey('articles', 'fk_origin_12345', 'author_id', 'users', 'id');
        $this->assertEquals($expected, $result[0]);
        if ($adapter->connection()->engine() === 'postgres') {
            $this->assertTrue($adapter->connection()->execute($result[0]));
        }
    }

    public function testAddIndex()
    {
        $adapter = new PostgresSchema('test');
        $expected = 'CREATE INDEX "owner_name" ON "accounts" (owner_id)';
        $result = $adapter->addIndex('accounts', 'owner_id', 'owner_name');
        $this->assertEquals($expected, $result);

        $expected = 'CREATE INDEX "owner_name" ON "accounts" (owner_id, tenant_id)';
        $result = $adapter->addIndex('accounts', ['owner_id', 'tenant_id'], 'owner_name');
        $this->assertEquals($expected, $result);

        $expected = 'CREATE UNIQUE INDEX "owner_name" ON "accounts" (owner_id)';
        $result = $adapter->addIndex('accounts', 'owner_id', 'owner_name', ['unique' => true]);
        $this->assertEquals($expected, $result);

        // Small sanity check
        if ($adapter->connection()->engine() === 'postgres') {
            $result = $adapter->addIndex('articles', 'title', 'index_title');
            $this->assertTrue($adapter->connection()->execute($result));
        }
        // The following command wont work on PGSQL, just testing the type
        $expected = 'CREATE PREFIX INDEX "idx_title" ON "topics" (title)';
        $result = $adapter->addIndex('topics', 'title', 'idx_title', ['type' => 'prefix']);
        $this->assertEquals($expected, $result);
    }

    /**
     * Leave SQL comments in place.
     */
    public function testChangeColumn()
    {
        $adapter = new PostgresSchema('test');

        // ALTER TABLE "articles" ALTER COLUMN "id" SET DATA TYPE INTEGER

        $statements = $adapter->changeColumn('articles', 'id', 'integer', ['limit' => '15']); // Ignored for this type on pgsql
        $this->assertEquals('ba0890c051a468e8d25c3d6a9d1ebf35', md5(json_encode($statements)));

        # check for syntax errors
        if ($adapter->connection()->engine() === 'postgres') {
            foreach ($statements as $statement) {
                $this->assertTrue($adapter->connection()->execute($statement));
            }
        }

        // 'ALTER TABLE "deals" ALTER COLUMN "amount" SET DATA TYPE DECIMAL(8,4)';
        $statements = $adapter->changeColumn('deals', 'amount', 'decimal', ['precision' => 8, 'scale' => 4]); // Ignored for this type on pgsql
        $this->assertEquals('e589a55b83286b440f90bb9204175f8f', md5(json_encode($statements)));

        # check for syntax errors
        if ($adapter->connection()->engine() === 'postgres') {
            foreach ($statements as $statement) {
                $this->assertTrue($adapter->connection()->execute($statement));
            }
        }

        // 'ALTER TABLE "deals" ALTER COLUMN "name" SET DATA TYPE VARCHAR(255), ALTER COLUMN "name" SET DEFAULT \'unkown\'';
        $statements = $adapter->changeColumn('deals', 'name', 'string', ['default' => 'unkown']); // Ignored for this type on pgsql
        $this->assertEquals('0b961ad551c728e430960ecad47fe1c9', md5(json_encode($statements)));

        if ($adapter->connection()->engine() === 'postgres') {
            foreach ($statements as $statement) {
                $this->assertTrue($adapter->connection()->execute($statement));
            }
        }

        //  'ALTER TABLE "deals" ALTER COLUMN "name" SET DATA TYPE VARCHAR(255), ALTER COLUMN "name" SET DEFAULT \'unkown\', ALTER COLUMN "name" SET NOT NULL';
        $statements = $adapter->changeColumn('deals', 'name', 'string', ['default' => 'unkown', 'null' => false]); // Ignored for this type on pgsql
        $this->assertEquals('b0597c132ae18e7775a905d47437838b', md5(json_encode($statements)));

        if ($adapter->connection()->engine() === 'postgres') {
            foreach ($statements as $statement) {
                $this->assertTrue($adapter->connection()->execute($statement));
            }
        }

        // 'ALTER TABLE "deals" ALTER COLUMN "name" SET DATA TYPE VARCHAR(255), ALTER COLUMN "name" SET DEFAULT NULL';
        $statements = $adapter->changeColumn('deals', 'name', 'string', ['default' => '']); // Ignored for this type on pgsql
        $this->assertEquals('e32bb9d3a6f5cd264692d845a19c9abb', md5(json_encode($statements)));

        if ($adapter->connection()->engine() === 'postgres') {
            foreach ($statements as $statement) {
                $this->assertTrue($adapter->connection()->execute($statement));
            }
        }

        // 'ALTER TABLE "deals" ALTER COLUMN "name" SET DATA TYPE VARCHAR(255), ALTER COLUMN "name" SET NOT NULL';
        $statements = $adapter->changeColumn('deals', 'name', 'string', ['null' => false]); // Ignored for this type on pgsql
        $this->assertEquals('83af110d7fc35b9fd5eaa56fda6854b8', md5(json_encode($statements)));

        if ($adapter->connection()->engine() === 'postgres') {
            foreach ($statements as $statement) {
                $this->assertTrue($adapter->connection()->execute($statement));
            }
        }
    }

    public function testColumnExists()
    {
        $adapter = new PostgresSchema('test');
        if ($adapter->connection()->engine() !== 'postgres') {
            $this->markTestSkipped('This is test is for pgsql');
        }

        $this->assertTrue($adapter->columnExists('posts', 'title'));
        $this->assertFalse($adapter->columnExists('posts', 'titles'));
    }

    public function testSchemaValue()
    {
        $adapter = new PostgresSchema('test');
        $this->assertEquals('NULL', $adapter->schemaValue(null));
        $this->assertEquals(3, $adapter->schemaValue(3));
        $this->assertEquals("'foo'", $adapter->schemaValue('foo'));
        $this->assertEquals('TRUE', $adapter->schemaValue(true));
        $this->assertEquals('FALSE', $adapter->schemaValue(false));
    }

    public function testColumns()
    {
        $adapter = new PostgresSchema('test');
        if ($adapter->connection()->engine() !== 'postgres') {
            $this->markTestSkipped('This is test is for pgsql');
        }
        $expected = ['id', 'title', 'body', 'published', 'created', 'modified'];
        $result = $adapter->columns('posts');
        $this->assertEquals($expected, $result);
    }

    public function testConnection()
    {
        $adapter = new PostgresSchema('test');
        $this->assertInstanceOf(Connection::class, $adapter->connection());
    }

    public function testDatasource()
    {
        $adapter = new PostgresSchema('foo');
        $this->assertEquals('foo', $adapter->datasource());
        $adapter->datasource('bar');
        $this->assertEquals('bar', $adapter->datasource());
    }

    /**
     * Create a FOO table which next tests will depend upon
     */
    public function testCreateTable()
    {
        $adapter = new PostgresSchema('test');

        $schema = [
            'id' => ['type' => 'integer', 'autoIncrement' => true],
            'name' => ['type' => 'string', 'default' => ''],
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
        $this->assertEquals('851886f807b42951dce4017a2686be57', md5(json_encode($statements)));

        if ($adapter->connection()->engine() !== 'postgres') {
            $this->markTestSkipped('This is test is for pgsql');
        }
        foreach ($statements as $statement) {
            $this->assertTrue($adapter->connection()->execute($statement));
        }
    }

    public function testChangeAutoIncrementSql()
    {
        $adapter = new PostgresSchema('test');
        $expected = 'ALTER SEQUENCE foo_id_seq RESTART WITH 1024';
        $result = $adapter->changeAutoIncrementSql('foo', 'id', 1024); # created in createTable
        $this->assertEquals($expected, $result);
        if ($adapter->connection()->engine() === 'postgres') {
            $this->assertTrue($adapter->connection()->execute($result));
        }
    }

    public function testDropTable()
    {
        $adapter = new PostgresSchema('test');
        $expected = 'DROP TABLE "foo" CASCADE';
        $result = $adapter->dropTableSql('foo'); # created in createTable
        $this->assertEquals($expected, $result);
        $this->assertEquals('DROP TABLE IF EXISTS "foo" CASCADE', $adapter->dropTableSql('foo', ['ifExists' => true]));
        if ($adapter->connection()->engine() === 'postgres') {
            $this->assertTrue($adapter->connection()->execute($result));
        }
    }

    public function testForeignKeyExists()
    {
        $stub = $this->getMock(PostgresSchema::class, ['foreignKeys']);
        // Configure the stub.

        $foreignKeys = [
            ['name' => 'fk_origin_e74ce85cbc', 'column' => 'author_name'],
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
        $adapter = new PostgresSchema('test');
        if ($adapter->connection()->engine() !== 'postgres') {
            $this->markTestSkipped('This test is for pgsql');
        }

        $sql = $adapter->addForeignKey('articles', 'fk_origin_12345', 'author_id', 'users', 'id');
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
        $adapter = new PostgresSchema('test');
        if ($adapter->connection()->engine() !== 'postgres') {
            $this->markTestSkipped('This test is for pgsql');
        }
        $indexes = $adapter->indexes('articles');

        $expected = [
            'name' => 'articles_pkey', // different on pgsql
            'column' => 'id',
            'type' => 'unique',
        ];
        $this->assertEquals($expected, $indexes[0]);

        // Tests multi column index  + normal index type
        $sql = $adapter->addIndex('articles', ['id', 'author_id'], 'test_multicolumn_index');
        $this->assertTrue($adapter->connection()->execute($sql));
        $indexes = $adapter->indexes('articles');

        $expected = [
            'name' => 'test_multicolumn_index',
            'column' => ['id', 'author_id'],
            'type' => 'index',
        ];
        $this->assertEquals($expected, $indexes[1]);
    }

    public function testRemoveColumn()
    {
        $adapter = new PostgresSchema('test');
        $expected = 'ALTER TABLE "articles" DROP COLUMN "modified"';
        $result = $adapter->removeColumn('articles', 'modified');
        $this->assertEquals($expected, $result[0]);

        if ($adapter->connection()->engine() === 'postgres') {
            $this->assertTrue($adapter->connection()->execute($result[0]));
        }
    }

    public function testRemoveColumns()
    {
        $adapter = new PostgresSchema('test');

        $expected = "ALTER TABLE \"articles\"\nDROP COLUMN \"created\",\nDROP COLUMN \"modified\"";
        $result = $adapter->removeColumns('articles', ['created', 'modified']);
        $this->assertEquals($expected, $result[0]);

        if ($adapter->connection()->engine() === 'postgres') {
            $this->assertTrue($adapter->connection()->execute($result[0]));
        }
    }

    /**
     * @depends testAddForeignKey
     */
    public function testRemoveForeignKey()
    {
        $adapter = new PostgresSchema('test');
        $expected = 'ALTER TABLE "articles" DROP CONSTRAINT "fk_origin_12345"';
        $result = $adapter->removeForeignKey('articles', 'fk_origin_12345');
        $this->assertEquals($expected, $result[0]);

        if ($adapter->connection()->engine() === 'postgres') {
            $sql = $adapter->addForeignKey('articles', 'fk_origin_12345', 'author_id', 'users', 'id');
            $this->assertTrue($adapter->connection()->execute($sql[0]));
            $this->assertTrue($adapter->connection()->execute($result[0]));
        }
    }

    public function testRemoveIndex()
    {
        $adapter = new PostgresSchema('test');
        $expected = 'DROP INDEX "author_id"'; // Different on pgsql no table name used
        $result = $adapter->removeIndex('articles', 'author_id');
        $this->assertEquals($expected, $result);
    }

    public function testRenameColumn()
    {
        $adapter = new PostgresSchema('test');
        /**
         * for pgsql comptability with older versions it needs to run some statements
         * this test for pgsql pgsql is a simple command
         */
        if ($adapter->connection()->engine() !== 'postgres') {
            $this->markTestSkipped('This test is for pgsql');
        }

        $expected = 'ALTER TABLE "articles" RENAME COLUMN "author_id" TO "user_id"'; // different on pgsql
        $result = $adapter->renameColumn('articles', 'author_id', 'user_id');
        $this->assertEquals($expected, $result[0]);
        $this->assertTrue($adapter->connection()->execute($result[0]));
    }

    /**
     * @depends testAddIndex
     */
    public function testRenameIndex()
    {
        $adapter = new PostgresSchema('test');
        $expected = 'ALTER INDEX "search_title" RENAME TO "title_search"'; // different on pgsql
        $result = $adapter->renameIndex('articles', 'search_title', 'title_search');
        $this->assertEquals($expected, $result[0]);

        if ($adapter->connection()->engine() === 'postgres') {
            $sql = $adapter->addIndex('articles', 'title', 'search_title');
            $this->assertTrue($adapter->connection()->execute($sql));
            $this->assertTrue($adapter->connection()->execute($result[0]));
        }
    }

    public function testRenameTable()
    {
        $adapter = new PostgresSchema('test');
        $expected = 'ALTER TABLE "articles" RENAME TO "artikel"'; // different on pgsql
        $result = $adapter->renameTable('articles', 'artikel');
        $this->assertEquals($expected, $result);
        if ($adapter->connection()->engine() === 'postgres') {
            $this->assertTrue($adapter->connection()->execute($result));
            $result = $adapter->renameTable('artikel', 'articles');
            $this->assertTrue($adapter->connection()->execute($result)); // rename back for fixture manager
        }
    }

    public function testShowCreateTable()
    {
        $adapter = new PostgresSchema('test');
        if ($adapter->connection()->engine() !== 'postgres') {
            $this->markTestSkipped('This test is for pgsql');
        }

        $result = $adapter->showCreateTable('articles');
        $this->assertEquals('a021a9a15d1444af83fed87aff779785', md5($result));
    }

    public function testTableExists()
    {
        $adapter = new PostgresSchema('test');
        if ($adapter->connection()->engine() !== 'postgres') {
            $this->markTestSkipped('This test is for pgsql');
        }
        $this->assertTrue($adapter->tableExists('articles'));
        $this->assertFalse($adapter->tableExists('foos'));
    }

    public function testTables()
    {
        $adapter = new PostgresSchema('test');
        if ($adapter->connection()->engine() !== 'postgres') {
            $this->markTestSkipped('This test is for pgsql');
        }

        $tables = $adapter->tables();

        $this->assertEquals(['articles', 'deals', 'posts', 'users'], $tables); // assert equals crashing phpunit
    }
}
