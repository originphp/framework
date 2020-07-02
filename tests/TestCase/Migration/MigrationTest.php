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

namespace Origin\Test\Migration;

use Origin\Migration\Migration;
use Origin\Model\ConnectionManager;
use Origin\Core\Exception\Exception;
use Origin\TestSuite\OriginTestCase;

use Origin\Core\Exception\InvalidArgumentException;
use Origin\Migration\Exception\IrreversibleMigrationException;

class CreateProductTableMigration extends Migration
{
    public function up(): void
    {
        $this->createTable('products', [
            'name' => 'string',
            'description' => 'text',
        ]);
    }
    public function down(): void
    {
        $this->dropTable('products');
    }
    public function reset(): void
    {
        $this->statements = [];
    }
}

class DidNotReadTheManualMigration extends Migration
{
    public function change(): void
    {
        $this->execute('SELECT * FROM read_the_manual');
    }

    public function reversable(): void
    {
        $this->execute('SELECT * FROM read_the_manual');
    }
}

class UsingExecuteMigration extends Migration
{
    public function up(): void
    {
        $this->execute('SELECT id,title,created from articles');
    }
    public function down(): void
    {
        $this->execute('SELECT id,title,created from articles');
    }
}

class MockMigration extends Migration
{
    protected $calledBy = null;

    public function setCalledBy(string $name = null)
    {
        $this->calledBy = $name;
    }
    public function calledBy(): string
    {
        return $this->calledBy;
    }

    public function reset()
    {
        $this->statements = [];
    }
}

class MigrationTest extends OriginTestCase
{
    protected $fixtures = ['Origin.Article', 'Origin.User', 'Origin.Deal'];

    public function adapter()
    {
        return ConnectionManager::get('test')->adapter();
    }

    /**
     * Return the migration object
     *
     * @return MockMigration
     */
    public function migration($calledBy = 'change')
    {
        $migration = new MockMigration($this->adapter());
        $migration->setCalledBy($calledBy);

        return $migration;
    }

    /**
     * @return void
     */
    public function testRealLifeExample()
    {
        $migration = $this->migration();

        $migration->createTable('products', [
            'owner_id' => 'integer',
            'name' => 'string',
            'manager_id' => 'integer',
            'status' => 'string',
            'description' => 'text'
        ]);
        $migration->addIndex('products', 'name');
        $migration->addIndex('products', 'status');

        $this->assertTrue($migration->indexExists('products', ['name' => 'idx_products_name']));
        $this->assertTrue($migration->indexExists('products', ['name' => 'idx_products_status']));

        $migration->addForeignKey('products', 'users', 'owner_id');

        $this->assertTrue($migration->indexExists('products', ['name' => 'idx_products_name']));
        $this->assertTrue($migration->indexExists('products', ['name' => 'idx_products_status']));

        $migration->addForeignKey('products', 'users', 'manager_id');

        $this->assertTrue($migration->foreignKeyExists('products', ['name' => 'fk_1af1da1b']));
        $this->assertTrue($migration->foreignKeyExists('products', ['name' => 'fk_1b2f2b89']));

        $this->assertTrue($migration->indexExists('products', ['name' => 'idx_products_name']));
        $this->assertTrue($migration->indexExists('products', ['name' => 'idx_products_status']));

        $migration->reset();

        $migration->rollback($migration->reverseStatements());
        $this->assertFalse($migration->tableExists('products'));
    }

    public function testCreateTable()
    {
        $migration = $this->migration();

        $options = ['engine' => 'InnoDB', 'autoIncrement' => 10000, 'charset' => 'utf8', 'collate' => 'utf8_unicode_ci'];

        $migration->createTable('products', [
            'name' => 'string',
            'description' => 'text',
            'column_1' => ['type' => 'string', 'default' => 'foo'],
            'column_2' => ['type' => 'string', 'default' => 'foo', 'null' => true],
            'column_3' => ['type' => 'string', 'default' => 'foo', 'null' => false],
            'column_4' => ['type' => 'string', 'null' => false],
            'column_5' => ['type' => 'string', 'null' => true],
            'column_6' => ['type' => 'VARCHAR', 'limit' => 5], // test non agnostic#$
        ], $options);

        $this->assertTrue($migration->columnExists('products', 'id'));

        $engine = $migration->connection()->engine();

        if ($engine !== 'sqlite') {
            $index = $engine === 'postgres' ? 'products_pkey' :  'PRIMARY';

            $this->assertTrue($migration->indexExists('products', ['name' => $index])); #$
        }

        $this->assertTrue($migration->columnExists('products', 'name', ['type' => 'string']));
        $this->assertTrue($migration->columnExists('products', 'description', ['type' => 'text']));

        $migration->reset(); // clear statements that have been run
        $migration->rollback($migration->reverseStatements());
        $this->assertFalse($migration->tableExists('products'));
    }

    public function testCreateJoinTable()
    {
        $migration = $this->migration();
        $migration->createJoinTable('contacts', 'tags');

        $this->assertTrue($migration->tableExists('contacts_tags'));
        $this->assertTrue($migration->columnExists('contacts_tags', 'contact_id'));
        $this->assertTrue($migration->columnExists('contacts_tags', 'tag_id'));

        $migration->reset(); // clear statements that have been run
        $migration->rollback($migration->reverseStatements());
        $this->assertFalse($migration->tableExists('contacts_tags'));
    }

    public function testDropTable()
    {
        $migration = $this->migration();
        $migration->dropTable('articles');
        $this->assertFalse($migration->tableExists('articles'));

        $migration->reset(); // clear statements that have been run
        $migration->rollback($migration->reverseStatements());
        $this->assertTrue($migration->tableExists('articles'));
    }

    public function testRenameTable()
    {
        $migration = $this->migration();
        $migration->renameTable('articles', 'ez_articles');

        $this->assertTrue($migration->tableExists('ez_articles'));

        $migration->reset(); // clear statements that have been run
        $migration->rollback($migration->reverseStatements());
        $this->assertFalse($migration->tableExists('ez_articles'));
    }

    /**
     * Test add column works
     */
    public function testAddColumn()
    {
        $migration = $this->migration();
        $migration->addColumn('articles', 'category_id', 'integer');
        $migration->addColumn('articles', 'status', 'string');

        $this->assertTrue($migration->columnExists('articles', 'category_id'));
        $this->assertTrue($migration->columnExists('articles', 'status'));

        $migration->reset(); // clear statements that have been run
        $migration->rollback($migration->reverseStatements());
        $this->assertFalse($migration->columnExists('articles', 'category_id'));
        $this->assertFalse($migration->columnExists('articles', 'status'));
    }

    public function testAddColumnComprehensive()
    {
        $migration = $this->migration();
        // NOT NULL column with default value NULL error

        $migration->createTable('articles2');

        $migration->addColumn('articles', 'category_id', 'integer');
        $migration->addColumn('articles', 'opens', 'integer', ['limit' => 3]); // #! changed
        $migration->addColumn('articles', 'amount', 'decimal', ['precision' => 5, 'scale' => 2]);
        $migration->addColumn('articles', 'balance', 'decimal'); // use defaults
        $migration->addColumn('articles', 'comment_1', 'string', ['default' => 'no comment']);
        $migration->addColumn('articles', 'comment_2', 'string', ['default' => 'foo', 'null' => true]);
        $migration->addColumn('articles', 'comment_3', 'string', ['default' => '123', 'null' => false]);

        /**
         * If the table is created,when rolling back cant test fields, but fixture inserts data will cause
         * error cannot be null so this particular column is put in new table.
         *
         */
        if ($migration->connection()->engine() !== 'sqlite') {
            $migration->addColumn('articles2', 'comment_4', 'string', ['null' => false]);
            $this->assertTrue($migration->columnExists('articles2', 'comment_4', ['null' => false]));
        }

        $migration->addColumn('articles', 'comment_5', 'string', ['null' => true]);

        $this->assertTrue($migration->columnExists('articles', 'category_id'));
        if ($migration->connection()->engine() === 'mysql') {
            $this->assertTrue($migration->columnExists('articles', 'opens', ['limit' => 3]));
        } else {
            $this->assertTrue($migration->columnExists('articles', 'opens')); // Integer does not limit on pgsql
        }

        $this->assertTrue($migration->columnExists('articles', 'amount', ['precision' => 5, 'scale' => 2]));
        $this->assertTrue($migration->columnExists('articles', 'balance', ['precision' => 10, 'scale' => 0]));
        $this->assertTrue($migration->columnExists('articles', 'comment_1', ['default' => 'no comment']));
        $this->assertTrue($migration->columnExists('articles', 'comment_2', ['default' => 'foo', 'null' => true]));
        $this->assertTrue($migration->columnExists('articles', 'comment_3', ['default' => '123', 'null' => false]));

        $this->assertTrue($migration->columnExists('articles', 'comment_5', ['null' => true]));

        $migration->reset(); // clear statements that have been run
        $migration->rollback($migration->reverseStatements());
        $this->assertFalse($migration->columnExists('articles', 'category_id'));
        $this->assertFalse($migration->tableExists('articles2'));
    }

    public function testColumns()
    {
        $migration = $this->migration();
        $expected = ['id', 'author_id', 'title', 'body', 'created', 'modified'];
        $this->assertSame($expected, $migration->columns('articles'));
    }

    public function testChangeColumnSetting()
    {
        $migration = $this->migration();

        $migration->changeColumn('articles', 'title', 'string', ['limit' => 10]);

        $this->assertTrue($migration->columnExists('articles', 'title', ['limit' => 10]));

        $migration->reset(); // clear statements that have been run
        $migration->rollback($migration->reverseStatements());
        $this->assertTrue($migration->columnExists('articles', 'title', ['limit' => 255]));
    }

    public function testChangeColumnType()
    {
        $migration = $this->migration();

        $migration->changeColumn('articles', 'body', 'string');

        $this->assertTrue($migration->columnExists('articles', 'body', ['type' => 'string']));

        $migration->reset(); // clear statements that have been run
        $migration->rollback($migration->reverseStatements());
        $this->assertTrue($migration->columnExists('articles', 'body', ['type' => 'text']));
    }

    /**
     * Null constraints work differently depending upon engine
     */
    public function testChangeColumnNullConstraint()
    {
        $migration = $this->migration();

        $this->assertTrue($migration->columnExists('articles', 'title', ['limit' => 255, 'null' => false]));
        //$migration->changeColumn('articles', 'title', 'string', ['limit' => 10, 'null' => true]);

        $migration->changeColumn('articles', 'title', 'string', ['limit' => 10]);

        $this->assertTrue($migration->columnExists('articles', 'title', ['limit' => 10, 'null' => true]));

        $migration->reset(); // clear statements that have been run
        $migration->rollback($migration->reverseStatements());
        $this->assertTrue($migration->columnExists('articles', 'title', ['limit' => 255, 'null' => false]));
    }

    /**
     * This really is for Postgres
     *
     * @return void
     */
    public function testChangeColumnDefaultConstraint()
    {
        $migration = $this->migration();
        $migration->changeColumn('deals', 'status', 'string', ['limit' => 40]);

        $this->assertTrue($migration->columnExists('deals', 'status', ['limit' => 40, 'default' => null]));

        $migration->reset(); // clear statements that have been run
        $migration->rollback($migration->reverseStatements());
        $this->assertTrue($migration->columnExists('deals', 'status', ['limit' => 50, 'default' => 'new']));
    }

    public function testRemoveColumn()
    {
        $migration = $this->migration();
        $migration->removeColumn('articles', 'body');

        $this->assertFalse($migration->columnExists('articles', 'body'));

        $migration->reset(); // clear statements that have been run
        $migration->rollback($migration->reverseStatements());
        $this->assertTrue($migration->columnExists('articles', 'body'));
    }

    public function testRemoveColumns()
    {
        # Prep
        $migration = $this->migration();
        $migration->addColumn('articles', 'remove_me', 'string', ['null' => true, 'default' => 'test']);
        $migration->addColumn('articles', 'remove_me_as_well', 'string', ['null' => true, 'default' => 'test']);

        # Test Up
        $migration = $this->migration();
        $migration->removeColumns('articles', ['remove_me', 'remove_me_as_well']);

        $this->assertFalse($migration->columnExists('articles', 'remove_me'));
        $this->assertFalse($migration->columnExists('articles', 'remove_me_as_well'));

        # Test Down
        $migration->reset(); // clear statements that have been run
        $migration->rollback($migration->reverseStatements());
        $this->assertTrue($migration->columnExists('articles', 'remove_me'));
        $this->assertTrue($migration->columnExists('articles', 'remove_me_as_well'));
    }

    public function testRenameColumn()
    {
        $migration = $this->migration();
        $migration->renameColumn('articles', 'title', 'article_title');

        $this->assertTrue($migration->columnExists('articles', 'article_title'));

        $migration->reset(); // clear statements that have been run
        $migration->rollback($migration->reverseStatements());
        $this->assertFalse($migration->columnExists('articles', 'article_title'));
    }

    public function testAddIndexAfterCreateTable()
    {
        $migration = $this->migration();

        # Create tables first
        $migration->createTable('c1', [
            'name' => 'string',
            'account_id' => 'integer',
            'owner_id' => 'integer',
        ]);
        $migration->addIndex('c1', 'account_id');

        $this->assertTrue(
            $migration->indexExists('c1', 'account_id')
        );

        $migration->reset(); // clear statements that have been run
        $migration->rollback($migration->reverseStatements());
        $this->assertFalse($migration->indexExists('c1', 'account_id'));
    }

    public function testAddIndex()
    {
        $migration = $this->migration();
        $migration->addIndex('articles', 'author_id');
        $migration->addIndex('articles', ['id', 'title']);
        $migration->addIndex('articles', 'created', ['unique' => true]);

        $this->assertTrue($migration->indexExists('articles', 'author_id'));
        $this->assertTrue($migration->indexExists('articles', ['id', 'title']));
        $this->assertTrue($migration->indexExists('articles', 'created'));

        $migration->reset(); // clear statements that have been run
        $migration->rollback($migration->reverseStatements());
        $this->assertFalse($migration->indexExists('articles', 'author_id'));
        $this->assertFalse($migration->indexExists('articles', ['id', 'title']));
        $this->assertFalse($migration->indexExists('articles', 'created'));
    }

    public function testRenameIndex()
    {
        $migration = $this->migration();
        $migration->addIndex('articles', 'author_id');

        $this->assertTrue($migration->indexExists('articles', 'author_id'));

        $migration = $this->migration();
        $migration->renameIndex('articles', 'idx_articles_author_id', 'idx_aaii');

        $this->assertTrue($migration->indexExists('articles', ['name' => 'idx_aaii']));

        $migration->reset(); // clear statements that have been run
        $migration->rollback($migration->reverseStatements());
        $this->assertFalse($migration->indexExists('articles', ['name' => 'idx_aaii']));
    }

    public function testRemoveIndex()
    {
        $migration = $this->migration();
        $migration->addIndex('articles', 'title');

        $this->assertTrue($migration->indexExists('articles', 'title'));

        $migration = $this->migration();
        $migration->removeIndex('articles', 'title');

        $this->assertFalse($migration->indexExists('articles', 'title'));

        $migration->reset(); // clear statements that have been run
        $migration->rollback($migration->reverseStatements());
        $this->assertTrue($migration->indexExists('articles', 'title'));
    }

    public function testAddForeignKey()
    {
        // Prepare
        $migration = $this->migration();
        $migration->addColumn('articles', 'user_id', 'integer', ['default' => 1000]);
        $migration->addForeignKey('articles', 'users');

        $this->assertTrue($migration->foreignKeyExists('articles', ['column' => 'user_id']));

        $migration->reset(); // clear statements that have been run
        $migration->rollback($migration->reverseStatements());
        $this->assertFalse($migration->foreignKeyExists('articles', ['column' => 'user_id']));
    }

    public function testAddForeignKeyOnUpdateAndDelete()
    {
        // Prepare
        $migration = $this->migration();
        $migration->addColumn('articles', 'user_id', 'integer', ['default' => 1000]);

        $migration = $this->migration();
        $migration->addForeignKey('articles', 'users', ['update' => 'cascade', 'delete' => 'restrict']);

        $expected = [
            'name' => 'fk_c05a10b6',  //fk_c05a10b6
            'table' => 'articles',
            'column' => 'user_id',
            'referencedTable' => 'users',
            'referencedColumn' => 'id',
            'update' => 'cascade',
            'delete' => 'restrict'
        ];
        $this->assertEquals($expected, $migration->foreignKeys('articles')[0]);

        $this->assertTrue($migration->foreignKeyExists('articles', ['column' => 'user_id']));

        $migration->reset(); // clear statements that have been run
        $migration->rollback($migration->reverseStatements());
        $this->assertFalse($migration->foreignKeyExists('articles', ['column' => 'user_id']));
    }

    /**
     * For sqlite have to do lots of magic, this acts as sanity check
     *
     * @return void
     */
    public function testRenameColumnWithIndex()
    {

        # Prepare fixture
        $migration = $this->migration();
        $migration->addIndex('users', ['id', 'email']);

        $this->assertTrue($migration->indexExists('users', ['id', 'email']));

        $migration->renameColumn('users', 'id', 'lng_id');

        $this->assertTrue($migration->columnExists('users', 'lng_id'));
        $this->assertFalse($migration->columnExists('users', 'id'));

        $this->assertTrue($migration->indexExists('users', ['name' => 'idx_users_id_email']));

        $migration->reset(); // clear statements that have been run
        $migration->rollback($migration->reverseStatements());

        $this->assertFalse($migration->columnExists('users', 'lng_id'));
        $this->assertTrue($migration->columnExists('users', 'id'));

        $this->assertFalse($migration->indexExists('users', ['name' => 'idx_users_id_email']));
    }

    public function testAddForeignKeyCustom()
    {
        # Prepare fixture
        $migration = $this->migration();
        $migration->renameColumn('users', 'id', 'lng_id');

        $this->assertTrue($migration->columnExists('users', 'lng_id'));

        # Migrate
        $migration = $this->migration();
        $migration->addForeignKey('articles', 'users', [
            'column' => 'author_id', 'primaryKey' => 'lng_id',
        ]);

        $this->assertTrue($migration->columnExists('users', 'lng_id'));
        $this->assertTrue($migration->foreignKeyExists('articles', ['column' => 'author_id']));

        $migration->reset(); // clear statements that have been run
        $migration->rollback($migration->reverseStatements());
        $this->assertFalse($migration->foreignKeyExists('articles', ['column' => 'author_id']));
    }

    public function testAddForeignKeyByName()
    {
        # Prepare fixture
        $migration = $this->migration();

        $migration->addColumn('articles', 'user_id', 'integer', ['default' => 1000]);

        # Migrate
        $migration = $this->migration();
        $migration->addForeignKey('articles', 'users', ['name' => 'myfk_001']);

        // in sqlite there is no way to extract foreignkey name
        $name = $migration->connection()->engine() === 'sqlite' ? 'fk_c05a10b6' : 'myfk_001';

        $this->assertTrue($migration->foreignKeyExists('articles', ['name' => $name]));

        $migration->reset(); // clear statements that have been run
        $migration->rollback($migration->reverseStatements());

        $this->assertFalse($migration->foreignKeyExists('articles', ['name' => $name]));
    }

    /**
     * This is important, as for sqlite we have to do alot of magic
     */
    public function testAddForeignKeyOnNewTable()
    {
        # Prepare fixtures
        $migration = $this->migration();
        $migration->createTable('contacts', [
            'name' => 'string',
            'account_id' => 'integer',
            'owner_id' => 'integer',
        ]);
        $migration->createTable('accounts', [
            'name' => 'string',
            'description' => 'text',
            'created' => 'datetime',
            'modified' => 'datetime'
        ]);
        $migration->addForeignKey('contacts', 'accounts');

        $this->assertTrue($migration->foreignKeyExists('contacts', 'account_id'));

        $migration->reset(); // clear statements that have been run
        $migration->rollback($migration->reverseStatements());
        $this->assertFalse($migration->tableExists('contacts'));
    }

    public function testRemoveForeignKey()
    {
        # Prepare fixtures
        $migration = $this->migration();
        $migration->createTable('contacts', [
            'name' => 'string',
            'account_id' => 'integer',
            'owner_id' => 'integer',
        ]);

        $migration->createTable('accounts', [
            'name' => 'string',
            'description' => 'text',
        ]);

        $migration->createTable('members', [
            'name' => 'string',
            'description' => 'text',
        ]);

        $migration->addForeignKey('contacts', 'accounts');

        $migration->addForeignKey('contacts', 'members', ['column' => 'owner_id', 'name' => 'fk_a1']); // fk_320405e8

        $name = $migration->connection()->engine() === 'sqlite' ? 'fk_caf8d09a' : 'fk_a1'; // fk_caf8d09a

        $this->assertTrue($migration->foreignKeyExists('contacts', 'account_id'));
        $this->assertTrue($migration->foreignKeyExists('contacts', ['name' => $name]));
        //fk_caf8d09a

        $otherStatements = $migration->reverseStatements();

        # Migrate
        $migration = $this->migration();
        $migration->removeForeignKey('contacts', 'accounts');
        $migration->removeForeignKey('contacts', ['name' => $name]);
        # add to end

        $this->assertFalse($migration->foreignKeyExists('contacts', 'account_id'));
        $this->assertFalse($migration->foreignKeyExists('contacts', ['name' => $name]));

        $migration->reset(); // clear statements that have been run
        $migration->rollback($migration->reverseStatements());
        $this->assertTrue($migration->foreignKeyExists('contacts', 'account_id'));
        $this->assertTrue($migration->foreignKeyExists('contacts', ['name' => $name]));

        $migration->rollback($otherStatements);
        $this->assertFalse($migration->tableExists('members'));
        $this->assertFalse($migration->tableExists('accounts'));
    }

    public function testUpDown()
    {
        $migration = new CreateProductTableMigration($this->adapter());
        $this->assertFalse($migration->tableExists('products'));
        $migration->start();
        $this->assertTrue($migration->tableExists('products'));

        $migration->rollback();
        $this->assertFalse($migration->tableExists('products'));
    }

    public function testExecute()
    {
        $migration = new  UsingExecuteMigration($this->adapter());
        $migration->start();
        $this->assertNotEmpty($migration->statements());

        $migration = new  UsingExecuteMigration($this->adapter());
        $migration->rollback();
        $this->assertNotEmpty($migration->statements());
    }

    public function testExecuteExecptionChange()
    {
        $migration = new DidNotReadTheManualMigration($this->adapter());
        $this->expectException(Exception::class);
        $migration->start();
    }

    public function testExecuteExecptionReversable()
    {
        $migration = new DidNotReadTheManualMigration($this->adapter());
        $this->expectException(Exception::class);
        $migration->rollback();
    }

    public function testIrreversibleMigrationException()
    {
        $migration = new CreateProductTableMigration($this->adapter());
        $this->expectException(IrreversibleMigrationException::class);
        $migration->throwIrreversibleMigrationException();
    }

    public function testStartDoesNotDoAnything()
    {
        $migration = new Migration($this->adapter());
        $this->expectException(Exception::class);
        $migration->start();
    }
    public function testRollbackDoesNotDoAnything()
    {
        $migration = new Migration($this->adapter());
        $this->expectException(Exception::class);
        $migration->rollback();
    }

    public function testTables()
    {
        $migration = new Migration($this->adapter());
        $this->assertIsArray($migration->tables());
    }
    public function testDropTableDoesNotExist()
    {
        $migration = new Migration($this->adapter());
        $this->expectException(Exception::class);
        $migration->dropTable('foo');
    }

    public function testAddColumnException()
    {
        $migration = new CreateProductTableMigration($this->adapter());
        $this->expectException(Exception::class);
        $migration->addColumn('bananas', 'name', 'string');
    }

    public function testChangeColumnException()
    {
        $migration = new CreateProductTableMigration($this->adapter());
        $this->expectException(Exception::class);
        $migration->changeColumn('bananas', 'name', 'string');
    }

    public function testChangeColumnDoesNotExistException()
    {
        $migration = new CreateProductTableMigration($this->adapter());
        $this->expectException(Exception::class);
        $migration->changeColumn('articles', 'does_not_exist', 'string');
    }

    public function testRenameColumnException()
    {
        $migration = new CreateProductTableMigration($this->adapter());
        $this->expectException(Exception::class);
        $migration->renameColumn('bananas', 'old', 'new');
    }

    public function testRenameColumnDoesNotExistException()
    {
        $migration = new CreateProductTableMigration($this->adapter());
        $this->expectException(Exception::class);
        $migration->renameColumn('articles', 'old', 'new');
    }

    public function testRemoveColumnException()
    {
        $migration = new CreateProductTableMigration($this->adapter());
        $this->expectException(Exception::class);
        $migration->removeColumn('bananas', 'old');
    }

    public function testRemoveColumnDoesNotExistException()
    {
        $migration = new CreateProductTableMigration($this->adapter());
        $this->expectException(Exception::class);
        $migration->removeColumn('articles', 'old');
    }

    public function testRemoveColumnnException()
    {
        $migration = new CreateProductTableMigration($this->adapter());
        $this->expectException(Exception::class);
        $migration->removeColumns('bananas', ['old']);
    }

    public function testRemoveColumnsDoesNotExistException()
    {
        $migration = new CreateProductTableMigration($this->adapter());
        $this->expectException(Exception::class);
        $migration->removeColumns('articles', ['old']);
    }

    public function testColumnExistsException()
    {
        $migration = new CreateProductTableMigration($this->adapter());
        $this->expectException(Exception::class);
        $migration->columnExists('bananas', 'old');
    }

    public function testColumnExists()
    {
        $migration = new CreateProductTableMigration($this->adapter());

        $this->assertTrue($migration->columnExists('articles', 'title'));
        $this->assertTrue($migration->columnExists('articles', 'title', ['type' => 'string']));
        $this->assertFalse($migration->columnExists('articles', 'title', ['type' => 'integer']));
        $this->assertFalse($migration->columnExists('articles', 'title', ['default' => 'nonya']));

        $this->assertTrue($migration->columnExists('articles', 'title', ['limit' => 255]));
        $this->assertFalse($migration->columnExists('articles', 'title', ['limit' => 10]));

        $this->assertTrue($migration->columnExists('deals', 'amount', ['precision' => 15]));
        $this->assertFalse($migration->columnExists('deals', 'amount', ['precision' => 12]));

        $this->assertFalse($migration->columnExists('deals', 'name', ['null' => true]));
        $this->assertTrue($migration->columnExists('deals', 'name', ['null' => false]));

        $this->assertTrue($migration->columnExists('deals', 'amount', ['scale' => 2]));
        $this->assertFalse($migration->columnExists('deals', 'amount', ['scale' => 4]));

        $this->assertTrue($migration->columnExists('deals', 'amount', ['precision' => 15, 'scale' => 2]));
    }

    public function testAddIndexException()
    {
        $migration = new CreateProductTableMigration($this->adapter());
        $this->expectException(Exception::class);
        $migration->addIndex('bananas', 'foo');
    }

    public function testRemoveIndexException()
    {
        $migration = new CreateProductTableMigration($this->adapter());
        $this->expectException(Exception::class);
        $migration->removeIndex('bananas', 'foo');
    }

    public function testAddForeignKeyException()
    {
        $migration = new CreateProductTableMigration($this->adapter());
        $this->expectException(Exception::class);
        $migration->addForeignKey('bananas', 'articles');
    }

    public function testAddForeignKeyDoesNotExistException()
    {
        $migration = new CreateProductTableMigration($this->adapter());
        $this->expectException(Exception::class);
        $migration->addForeignKey('articles', 'bananas');
    }

    public function testRemoveForeignKeyException()
    {
        $migration = new CreateProductTableMigration($this->adapter());
        $this->expectException(Exception::class);
        $migration->removeForeignKey('bananas', 'articles');
    }

    public function testRemoveForeignKeyInvalidArgument()
    {
        $migration = new CreateProductTableMigration($this->adapter());
        $this->expectException(InvalidArgumentException::class);
        $migration->removeForeignKey('articles', []);
    }

    public function testForeignKeyExistsException()
    {
        $migration = new CreateProductTableMigration($this->adapter());
        $this->expectException(Exception::class);
        $migration->foreignKeyExists('bananas', 'abc123');
    }

    public function testFetchRow()
    {
        $migration = new CreateProductTableMigration($this->adapter());
        $result = $migration->fetchRow('SELECT * FROM articles');
        $expected = [
            'id' => 1000, 'author_id' => 1001, 'title' => 'Article #1', 'body' => 'Description about article #1', 'created' => '2019-03-27 13:10:00', 'modified' => '2019-03-27 13:12:00',
        ];
        $this->assertEquals($expected, $result);
    }

    public function testFetchAll()
    {
        $migration = new CreateProductTableMigration($this->adapter());
        $result = $migration->fetchAll('SELECT * FROM articles LIMIT 1');
        $expected = [
            'id' => 1000, 'author_id' => 1001, 'title' => 'Article #1', 'body' => 'Description about article #1', 'created' => '2019-03-27 13:10:00', 'modified' => '2019-03-27 13:12:00',
        ];
        $this->assertEquals([$expected], $result);
    }
}
