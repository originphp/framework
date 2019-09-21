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

namespace Origin\Test\Migration;

use Origin\Exception\Exception;
use Origin\Migration\Migration;
use Origin\Model\ConnectionManager;
use Origin\TestSuite\OriginTestCase;

use Origin\Exception\InvalidArgumentException;
use Origin\Migration\Exception\IrreversibleMigrationException;

class CreateProductTableMigration extends Migration
{
    public function up()
    {
        $this->createTable('products', [
            'name' => 'string',
            'description' => 'text',
        ]);
    }
    public function down()
    {
        $this->dropTable('products');
    }
    public function reset()
    {
        $this->statements = [];
    }
}

class DidNotReadTheManualMigration extends Migration
{
    public function change()
    {
        $this->execute('SELECT * FROM read_the_manual');
    }

    public function reversable()
    {
        $this->execute('SELECT * FROM read_the_manual');
    }
}

class UsingExecuteMigration extends Migration
{
    public function up()
    {
        $this->execute('SELECT id,title,created from articles');
    }
    public function down()
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
    public function calledBy() : string
    {
        return $this->calledBy;
    }

    public function reset()
    {
        $this->statements = [];
    }

    /**
     * Keep code short
     *
     * @return array
     */
    public function invokeStart()
    {
        $this->start();
        $reversableStatements = $this->reverseStatements();
        $this->statements = [];

        return $reversableStatements;
    }
}

class MigrationTest extends OriginTestCase
{
    public $fixtures = ['Origin.Article','Origin.User','Origin.Deal'];

    public function adapter()
    {
        return ConnectionManager::get('test')->adapter();
    }

    /**
     * Return the migration object
     *
     * @return \Origin\Migration\Migration
     */
    public function migration($calledBy = 'change')
    {
        $migration = new MockMigration($this->adapter());
        $migration->setCalledBy($calledBy);

        return $migration;
    }

    public function testCreateTable()
    {
        $migration = $this->migration();

        $options = ['engine' => 'InnoDB','autoIncrement' => 10000,'charset' => 'utf8','collate' => 'utf8_unicode_ci'];

        $index = 'PRIMARY';
        if ($migration->connection()->engine() === 'pgsql') {
            $index = 'products_pkey';
        }

        $migration->createTable('products', [
            'name' => 'string',
            'description' => 'text',
            'column_1' => ['type' => 'string','default' => 'foo'],
            'column_2' => ['type' => 'string','default' => 'foo','null' => true],
            'column_3' => ['type' => 'string','default' => 'foo','null' => false],
            'column_4' => ['type' => 'string','null' => false],
            'column_5' => ['type' => 'string','null' => true],
            'column_6' => ['type' => 'VARCHAR','limit' => 5], // test non agnostic#$
        ], $options);
        
        $this->assertEquals(2, count($migration->statements())); // second statement should be setting autoincrement

        $reversableStatements = $migration->invokeStart();
      
        $this->assertTrue($migration->columnExists('products', 'id'));
        $this->assertTrue($migration->indexExists('products', ['name' => $index])); #$

        $this->assertTrue($migration->columnExists('products', 'name', ['type' => 'string']));
        $this->assertTrue($migration->columnExists('products', 'description', ['type' => 'text']));
        
        $migration->rollback($reversableStatements);
        $this->assertFalse($migration->tableExists('products'));
    }

    public function testCreateJoinTable()
    {
        $migration = $this->migration();
        $migration->createJoinTable('contacts', 'tags');
     
        $reversableStatements = $migration->invokeStart();
        $this->assertTrue($migration->tableExists('contacts_tags'));
        $this->assertTrue($migration->columnExists('contacts_tags', 'contact_id'));
        $this->assertTrue($migration->columnExists('contacts_tags', 'tag_id'));

        $migration->rollback($reversableStatements);
        $this->assertFalse($migration->tableExists('contacts_tags'));
    }
    
    public function testDropTable()
    {
        $migration = $this->migration();
        $migration->dropTable('articles');
        
        $reversableStatements = $migration->invokeStart();
        $this->assertFalse($migration->tableExists('articles'));
  
        $migration->rollback($reversableStatements);
        $this->assertTrue($migration->tableExists('articles'));
    }

    public function testRenameTable()
    {
        $migration = $this->migration();
        $migration->renameTable('articles', 'ez_articles');
        
        $reversableStatements = $migration->invokeStart();
        $this->assertTrue($migration->tableExists('ez_articles'));

        $migration->rollback($reversableStatements);
        $this->assertFalse($migration->tableExists('ez_articles'));
    }

    public function testAddColumn()
    {
        $migration = $this->migration();

        $migration->createTable('articles2');
        $reversableStatements = $migration->invokeStart(); // Can only add columns on existing tables

        $migration = $this->migration();
        $migration->addColumn('articles', 'category_id', 'integer');
        $migration->addColumn('articles', 'opens', 'integer', ['limit' => 3]); // #! changed
        $migration->addColumn('articles', 'amount', 'decimal', ['precision' => 5,'scale' => 2]);
        $migration->addColumn('articles', 'balance', 'decimal'); // use defaults
        $migration->addColumn('articles', 'comment_1', 'string', ['default' => 'no comment']);
        $migration->addColumn('articles', 'comment_2', 'string', ['default' => 'foo','null' => true]);
        $migration->addColumn('articles', 'comment_3', 'string', ['default' => '123','null' => false]);
        /**
         * If the table is created,when rolling back cant test fields, but fixture inserts data will cause
         * error cannot be null so this particular column is put in new table.
         */
        $migration->addColumn('articles2', 'comment_4', 'string', ['null' => false]);
        $migration->addColumn('articles', 'comment_5', 'string', ['null' => true]);

        $reversableStatements = array_merge($migration->invokeStart(), $reversableStatements); # add to end
        
        $this->assertTrue($migration->columnExists('articles', 'category_id'));
        if ($migration->connection()->engine() === 'mysql') {
            $this->assertTrue($migration->columnExists('articles', 'opens', ['limit' => 3]));
        } else {
            $this->assertTrue($migration->columnExists('articles', 'opens')); // Integer does not limit on pgsql
        }
       
        $this->assertTrue($migration->columnExists('articles', 'amount', ['precision' => 5,'scale' => 2]));
        $this->assertTrue($migration->columnExists('articles', 'balance', ['precision' => 10,'scale' => 0]));
        $this->assertTrue($migration->columnExists('articles', 'comment_1', ['default' => 'no comment']));
        $this->assertTrue($migration->columnExists('articles', 'comment_2', ['default' => 'foo','null' => true]));
        $this->assertTrue($migration->columnExists('articles', 'comment_3', ['default' => '123','null' => false]));
        $this->assertTrue($migration->columnExists('articles2', 'comment_4', ['null' => false]));
        $this->assertTrue($migration->columnExists('articles', 'comment_5', ['null' => true]));
        
        $migration->rollback($reversableStatements);
        $this->assertFalse($migration->columnExists('articles', 'category_id'));
        $this->assertFalse($migration->tableExists('articles2'));
    }

    public function testColumns()
    {
        $migration = $this->migration();
        $expected = ['id','author_id','title','body','created','modified'];
        $this->assertSame($expected, $migration->columns('articles'));
    }

    public function testChangeColumn()
    {
        $migration = $this->migration();
 
        $migration->changeColumn('articles', 'title', 'string', ['limit' => 10]);
        $migration->changeColumn('articles', 'body', 'string');
    
        $reversableStatements = $migration->invokeStart();
        $this->assertTrue($migration->columnExists('articles', 'title', ['limit' => 10]));
        $this->assertTrue($migration->columnExists('articles', 'body', ['type' => 'string']));
        
        $migration->rollback($reversableStatements);
        $this->assertTrue($migration->columnExists('articles', 'title', ['limit' => 255]));
        $this->assertTrue($migration->columnExists('articles', 'body', ['type' => 'text']));
    }

    /**
     * Null constraints work differently depending upon engine
     */
    public function testChangeColumnNullConstraint()
    {
        $migration = $this->migration();
        $migration->changeColumn('articles', 'title', 'string', ['limit' => 10]);

        $reversableStatements = $migration->invokeStart();
        $this->assertTrue($migration->columnExists('articles', 'title', ['limit' => 10,'null' => true]));
   
        $migration->rollback($reversableStatements);
        $this->assertTrue($migration->columnExists('articles', 'title', ['limit' => 255,'null' => false]));
    }

    /**
     * This really is for Pgsql
     *
     * @return void
     */
    public function testChangeColumnDefaultConstraint()
    {
        $migration = $this->migration();
        $migration->changeColumn('deals', 'status', 'string', ['limit' => 40]);

        $reversableStatements = $migration->invokeStart();
        $this->assertTrue($migration->columnExists('deals', 'status', ['limit' => 40,'default' => null]));
       
        $migration->rollback($reversableStatements);
        $this->assertTrue($migration->columnExists('deals', 'status', ['limit' => 50,'default' => 'new']));
    }

    public function testRemoveColumn()
    {
        $migration = $this->migration();
        $migration->removeColumn('articles', 'body');
        
        $reversableStatements = $migration->invokeStart();
        $this->assertFalse($migration->columnExists('articles', 'body'));

        $migration->rollback($reversableStatements);
        $this->assertTrue($migration->columnExists('articles', 'body'));
    }

    public function testRemoveColumns()
    {
        # Prep
        $migration = $this->migration();
        $migration->addColumn('articles', 'remove_me', 'string', ['null' => true,'default' => 'test']);
        $migration->addColumn('articles', 'remove_me_as_well', 'string', ['null' => true,'default' => 'test']);
        $migration->start();

        # Test Up
        $migration = $this->migration();
        $migration->removeColumns('articles', ['remove_me','remove_me_as_well']);
        $reversableStatements = $migration->invokeStart();
        $this->assertFalse($migration->columnExists('articles', 'remove_me'));
        $this->assertFalse($migration->columnExists('articles', 'remove_me_as_well'));

        # Test Down
        $migration->rollback($reversableStatements);
        $this->assertTrue($migration->columnExists('articles', 'remove_me'));
        $this->assertTrue($migration->columnExists('articles', 'remove_me_as_well'));
    }

    public function testRenameColumn()
    {
        $migration = $this->migration();
        $migration->renameColumn('articles', 'title', 'article_title');
        
        $reversableStatements = $migration->invokeStart();
        $this->assertTrue($migration->columnExists('articles', 'article_title'));

        $migration->rollback($reversableStatements);
        $this->assertFalse($migration->columnExists('articles', 'article_title'));
    }

    public function testAddIndex()
    {
        $migration = $this->migration();
        $migration->addIndex('articles', 'author_id');
        $migration->addIndex('articles', ['id','title']);
        $migration->addIndex('articles', 'created', ['unique' => true]);
       
        $reversableStatements = $migration->invokeStart();
        $this->assertTrue($migration->indexExists('articles', 'author_id'));
        $this->assertTrue($migration->indexExists('articles', ['id','title']));
        $this->assertTrue($migration->indexExists('articles', 'created'));
    
        $migration->rollback($reversableStatements);
        $this->assertFalse($migration->indexExists('articles', 'author_id'));
        $this->assertFalse($migration->indexExists('articles', ['id','title']));
        $this->assertFalse($migration->indexExists('articles', 'created'));
    }

    public function testRenameIndex()
    {
        $migration = $this->migration();
        $migration->addIndex('articles', 'author_id');
        $reversableStatements = $migration->invokeStart();
        $this->assertTrue($migration->indexExists('articles', 'author_id'));
        
        $migration = $this->migration();
        $migration->renameIndex('articles', 'articles_author_id_index', 'aaii_index');

        $reversableStatements = array_merge($migration->invokeStart(), $reversableStatements);
       
        $this->assertTrue($migration->indexExists('articles', ['name' => 'aaii_index']));
        
        $migration->rollback($reversableStatements);
        $this->assertFalse($migration->indexExists('articles', ['name' => 'aaii_index']));
    }

    public function testRemoveIndex()
    {
        $migration = $this->migration();
        $migration->addIndex('articles', 'title');
        $reversableStatements = $migration->invokeStart();
        $this->assertTrue($migration->indexExists('articles', 'title'));
      
        $migration = $this->migration();
        $migration->removeIndex('articles', 'title');
        $reversableStatements = $migration->invokeStart();
        $this->assertFalse($migration->indexExists('articles', 'title'));
      
        $migration->rollback($reversableStatements);
        $this->assertTrue($migration->indexExists('articles', 'title'));
    }
   
    public function testAddForeignKey()
    {
        // Prepare
        $migration = $this->migration();
        $migration->addColumn('articles', 'user_id', 'integer', ['default' => 1000]);
        $reversableStatements = $migration->invokeStart();

        $migration = $this->migration();
        $migration->addForeignKey('articles', 'users');
        $reversableStatements = array_merge($migration->invokeStart(), $reversableStatements);
        $this->assertTrue($migration->foreignKeyExists('articles', ['column' => 'user_id']));

        $migration->rollback($reversableStatements);
        $this->assertFalse($migration->foreignKeyExists('articles', ['column' => 'user_id']));
    }

    public function testAddForeignKeyOnUpdateAndDelete()
    {
        // Prepare
        $migration = $this->migration();
        $migration->addColumn('articles', 'user_id', 'integer', ['default' => 1000]);
        $reversableStatements = $migration->invokeStart();

        $migration = $this->migration();
        $migration->addForeignKey('articles', 'users', ['update' => 'cascade','delete' => 'restrict']);

        $reversableStatements = array_merge($migration->invokeStart(), $reversableStatements);
        $this->assertTrue($migration->foreignKeyExists('articles', ['column' => 'user_id']));

        $migration->rollback($reversableStatements);
        $this->assertFalse($migration->foreignKeyExists('articles', ['column' => 'user_id']));
    }

    public function testAddForeignKeyCustom()
    {
       
        # Prepare
        $migration = $this->migration();
        $migration->renameColumn('users', 'id', 'lng_id');
        $reversableStatements = $migration->invokeStart();
        $this->assertTrue($migration->columnExists('users', 'lng_id'));

        $migration = $this->migration();
        $migration->addForeignKey('articles', 'users', [
            'column' => 'author_id','primaryKey' => 'lng_id',
        ]);
       
        $reversableStatements = array_merge($migration->invokeStart(), $reversableStatements);
        $this->assertTrue($migration->columnExists('users', 'lng_id'));
        $this->assertTrue($migration->foreignKeyExists('articles', ['column' => 'author_id']));

        $migration->rollback($reversableStatements);
        $this->assertFalse($migration->foreignKeyExists('articles', ['column' => 'author_id']));
    }

    public function testAddForeignKeyByName()
    {
        $migration = $this->migration();
        $migration->addColumn('articles', 'user_id', 'integer', ['default' => 1000]);
        $reversableStatements = $migration->invokeStart();

        $migration = $this->migration();
        $migration->addForeignKey('articles', 'users', ['name' => 'myfk_001']);
        $reversableStatements = array_merge($migration->invokeStart(), $reversableStatements);
        $this->assertTrue($migration->foreignKeyExists('articles', ['name' => 'myfk_001']));

        $migration->rollback($reversableStatements);
        $this->assertFalse($migration->foreignKeyExists('articles', ['name' => 'myfk_001']));
    }

    public function testRemoveForeignKey()
    {
        $migration = $this->migration();

        # Create tables first
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
        $migration->addForeignKey('contacts', 'members', ['column' => 'owner_id','name' => 'fk_a1']);
        $undotables = $migration->invokeStart(); # add to end
       
        $this->assertTrue($migration->foreignKeyExists('contacts', 'account_id'));
        $this->assertTrue($migration->foreignKeyExists('contacts', ['name' => 'fk_a1']));

        # Test
        $migration = $this->migration();
        $migration->removeForeignKey('contacts', 'accounts');
        $migration->removeForeignKey('contacts', ['name' => 'fk_a1']);
        $reversableStatements = $migration->invokeStart(); # add to end

        $this->assertFalse($migration->foreignKeyExists('contacts', 'account_id'));
        $this->assertFalse($migration->foreignKeyExists('contacts', ['name' => 'fk_a1']));
            
        $migration->rollback($reversableStatements);
        $this->assertTrue($migration->foreignKeyExists('contacts', 'account_id'));
        $this->assertTrue($migration->foreignKeyExists('contacts', ['name' => 'fk_a1']));
        $migration->rollback($undotables);
    }

    public function testUpDown()
    {
        $migration = new CreateProductTableMigration($this->adapter());
        $this->assertFalse($migration->tableExists('products'));
        $migration->start();
        $this->assertTrue($migration->tableExists('products'));

        $migration->reset();
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

        $this->assertTrue($migration->columnExists('deals', 'amount', ['precision' => 15,'scale' => 2]));
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
            'id' => 1000,'author_id' => 1001,'title' => 'Article #1','body' => 'Description about article #1', 'created' => '2019-03-27 13:10:00','modified' => '2019-03-27 13:12:00',
        ];
        $this->assertEquals($expected, $result);
    }

    public function testFetchAll()
    {
        $migration = new CreateProductTableMigration($this->adapter());
        $result = $migration->fetchAll('SELECT * FROM articles LIMIT 1');
        $expected = [
            'id' => 1000,'author_id' => 1001,'title' => 'Article #1','body' => 'Description about article #1', 'created' => '2019-03-27 13:10:00','modified' => '2019-03-27 13:12:00',
        ];
        $this->assertEquals([$expected], $result);
    }
}
