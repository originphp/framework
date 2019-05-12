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

use Origin\Migration\Migration;
use Origin\Migration\Adapter\MysqlAdapter;
use Origin\TestSuite\OriginTestCase;
use Origin\Exception\Exception;
use Origin\Core\Logger;

class MockMigration extends Migration
{
    public function debug()
    {
        $this->change();
        debug($this->statements());
        die();
    }
}

class MigrationTest extends OriginTestCase
{
    public $fixtures = ['Origin.Article','Origin.User'];

    public function getAdapter()
    {
        return new MysqlAdapter();
    }
    public function testAddColumn()
    {
        $migration = new MockMigration($this->getAdapter(), ['datasource'=>'test']);

        $migration->createTable('articles2');

        $migration->addColumn('articles', 'category_id', 'integer');
        $migration->addColumn('articles', 'opens', 'integer', ['limit'=>3]);
        $migration->addColumn('articles', 'amount', 'decimal', ['precision'=>5,'scale'=>2]);
        $migration->addColumn('articles', 'balance', 'decimal'); // use defaults
        $migration->addColumn('articles', 'comment_1', 'string', ['default'=>'no comment']);
        $migration->addColumn('articles', 'comment_2', 'string', ['default'=>'foo','null'=>true]);
        $migration->addColumn('articles', 'comment_3', 'string', ['default'=>'123','null'=>false]);
        /**
         * If the table is created,when rolling back cant test fields, but fixture inserts data will cause
         * error cannot be null so this particular column is put in new table.
         */
        $migration->addColumn('articles2', 'comment_4', 'string', ['null'=>false]);
        $migration->addColumn('articles', 'comment_5', 'string', ['null'=>true]);

        $migration->start();
       $this->assertTrue($migration->columnExists('articles', 'category_id'));
        $this->assertTrue($migration->columnExists('articles', 'opens', ['limit'=>3]));
        $this->assertTrue($migration->columnExists('articles', 'amount', ['precision'=>5,'scale'=>2]));
        $this->assertTrue($migration->columnExists('articles', 'balance', ['precision'=>10,'scale'=>0]));
        $this->assertTrue($migration->columnExists('articles', 'comment_1', ['default'=>'no comment']));
        $this->assertTrue($migration->columnExists('articles', 'comment_2', ['default'=>'foo','null'=>true]));
        $this->assertTrue($migration->columnExists('articles', 'comment_3', ['default'=>'123','null'=>false]));
        $this->assertTrue($migration->columnExists('articles2', 'comment_4', ['null'=>false]));
        $this->assertTrue($migration->columnExists('articles', 'comment_5', ['null'=>true]));
       
        $migration->rollback();
        $this->assertFalse($migration->columnExists('articles', 'category_id'));
    }

    public function testAddForeignKey()
    {
        $migration = new MockMigration($this->getAdapter(), ['datasource'=>'test']);

        $migration->addColumn('articles', 'user_id', 'integer', ['default'=>1000]);
       
        $migration->addForeignKey('articles', 'users');

        $migration->start();

        $this->assertTrue($migration->foreignKeyExists('articles', ['column'=>'user_id']));

        $migration->rollback();
        $this->assertFalse($migration->foreignKeyExists('articles', ['column'=>'user_id']));
    }

    public function testAddForeignKeyCustom()
    {
        $migration = new MockMigration($this->getAdapter(), ['datasource'=>'test']);

        $migration->renameColumn('users', 'id', 'lng_id');
        $migration->addForeignKey('articles', 'users', [
            'column'=>'author_id','primaryKey'=>'lng_id'
            ]);
       
        $migration->start();

        $this->assertTrue($migration->columnExists('users', 'lng_id'));
        $this->assertTrue($migration->foreignKeyExists('articles', ['column'=>'author_id']));


        $migration->rollback();
        $this->assertFalse($migration->columnExists('users', 'lng_id'));
        $this->assertFalse($migration->foreignKeyExists('articles', ['column'=>'author_id']));
    }

    public function testAddForeignKeyByName()
    {
        $migration = new MockMigration($this->getAdapter(), ['datasource'=>'test']);

        $migration->addColumn('articles', 'user_id', 'integer', ['default'=>1000]);
       
        $migration->addForeignKey('articles', 'users', ['name'=>'myfk_001']);

        $migration->start();

        $this->assertTrue($migration->foreignKeyExists('articles', ['name'=>'myfk_001']));

        $migration->rollback();
        $this->assertFalse($migration->foreignKeyExists('articles', ['name'=>'myfk_001']));
    }

    public function testAddIndex()
    {
        $migration = new MockMigration($this->getAdapter(), ['datasource'=>'test']);
        $migration->addIndex('articles', 'author_id');
        $migration->addIndex('articles', ['id','title']);
        $migration->addIndex('articles', 'created', ['unique'=>true]);
       
        $migration->start();
        $this->assertTrue($migration->indexExists('articles', 'author_id'));
        $this->assertTrue($migration->indexExists('articles', ['id','title']));
        $this->assertTrue($migration->indexExists('articles', 'created'));
    
        $migration->rollback();
        $this->assertFalse($migration->indexExists('articles', 'author_id'));
        $this->assertFalse($migration->indexExists('articles', ['id','title']));
        $this->assertFalse($migration->indexExists('articles', 'created'));
    }

    public function testRenameIndex()
    {
        $migration = new MockMigration($this->getAdapter(), ['datasource'=>'test']);
        $migration->addIndex('articles', 'author_id');
        $migration->start();
        $this->assertTrue($migration->indexExists('articles', 'author_id'));
        
        $migration = new MockMigration($this->getAdapter(), ['datasource'=>'test']);
        $migration->renameIndex('articles', 'articles_author_id_index', 'aaii_index');
        $migration->start();
        
        $this->assertTrue($migration->indexExists('articles', ['name'=>'aaii_index']));
        
        $migration->rollback();
        $this->assertFalse($migration->indexExists('articles',['name'=>'aaii_index']));
    }

    public function testChangeColumn()
    {
        $migration = new MockMigration($this->getAdapter(), ['datasource'=>'test']);
 
        $migration->changeColumn('articles', 'title', 'string', ['limit'=>10]);
        $migration->changeColumn('articles', 'body', 'string');
    
        $migration->start();
        $this->assertTrue($migration->columnExists('articles', 'title', ['limit'=>10]));
        $this->assertTrue($migration->columnExists('articles', 'body', ['type'=>'string']));
        $migration->rollback();
        $this->assertTrue($migration->columnExists('articles', 'title', ['limit'=>255]));
        $this->assertTrue($migration->columnExists('articles', 'body', ['type'=>'text']));
    }

    public function testColumns()
    {
        $migration = new MockMigration($this->getAdapter(), ['datasource'=>'test']);
        $expected = ['id','author_id','title','body','created','modified'];
        $this->assertSame($expected, $migration->columns('articles'));
    }

    public function testCreateTable()
    {
        $migration = new MockMigration($this->getAdapter(), ['datasource'=>'test']);
        $migration->createTable('products', [
            'name' => 'string',
            'description' => 'text',
            'column_1' => ['type'=>'string','default'=>'foo'],
            'column_2' => ['type'=>'string','default'=>'foo','null'=>true],
            'column_3' => ['type'=>'string','default'=>'foo','null'=>false],
            'column_4' => ['type'=>'string','null'=>false],
            'column_5' => ['type'=>'string','null'=>true],
            'column_6' => ['type'=>'INT','limit'=>5] // test non agnostic
        ], ['options'=>'ENGINE=InnoDB DEFAULT CHARSET=utf8']);
        
    
        $migration->start();
        $this->assertTrue($migration->columnExists('products', 'id'));
        $this->assertTrue($migration->indexExists('products',['name'=>'PRIMARY']));

        $this->assertTrue($migration->columnExists('products', 'name', ['type'=>'string']));
        $this->assertTrue($migration->columnExists('products', 'description', ['type'=>'text']));
     
        $migration->rollback();
        $this->assertFalse($migration->tableExists('products'));
    }

    public function testCreateJoinTable()
    {
        $migration = new MockMigration($this->getAdapter(), ['datasource'=>'test']);
        $migration->createJoinTable('contacts', 'tags');
     
        $migration->start();
        $this->assertTrue($migration->tableExists('contacts_tags'));
        $this->assertTrue($migration->columnExists('contacts_tags', 'contact_id'));
        $this->assertTrue($migration->columnExists('contacts_tags', 'tag_id'));

        $migration->rollback();
        $this->assertFalse($migration->tableExists('contacts_tags'));
    }

    public function testRemoveColumn()
    {
        $migration = new MockMigration($this->getAdapter(), ['datasource'=>'test']);
        $migration->removeColumn('articles', 'body');
        
        $migration->start();
        $this->assertFalse($migration->columnExists('articles', 'body'));
        $migration->rollback();
        $this->assertTrue($migration->columnExists('articles', 'body'));
    }

    public function testRemoveColumns()
    {
        $migration = new MockMigration($this->getAdapter(), ['datasource'=>'test']);
        $migration->removeColumns('articles', ['title','created']);
        
        $migration->start();
        $this->assertFalse($migration->columnExists('articles', 'title'));
        $this->assertFalse($migration->columnExists('articles', 'created'));

        $migration->rollback();
        $this->assertTrue($migration->columnExists('articles', 'title'));
        $this->assertTrue($migration->columnExists('articles', 'created'));
    }

    public function testRenameColumn()
    {
        $migration = new MockMigration($this->getAdapter(), ['datasource'=>'test']);
        $migration->renameColumn('articles', 'title', 'article_title');
        
        $migration->start();
        $this->assertTrue($migration->columnExists('articles', 'article_title'));

        $migration->rollback();
        $this->assertFalse($migration->columnExists('articles', 'article_title'));
    }

    public function testRenameTable()
    {
        $migration = new MockMigration($this->getAdapter(), ['datasource'=>'test']);
        $migration->renameTable('articles', 'ez_articles');
        
        $migration->start();
        $this->assertTrue($migration->tableExists('ez_articles'));

        $migration->rollback();
        $this->assertFalse($migration->tableExists('ez_articles'));
    }

    public function testDropTable()
    {
        $migration = new MockMigration($this->getAdapter(), ['datasource'=>'test']);
        $migration->dropTable('articles');
        $migration->start();
        $this->assertFalse($migration->tableExists('articles'));
        $migration->rollback();
        $this->assertTrue($migration->tableExists('articles'));
    }

    public function testRemoveIndex(){
        $migration = new MockMigration($this->getAdapter(), ['datasource'=>'test']);
        $migration->addIndex('articles','title');
        $migration->start();
        $this->assertTrue($migration->indexExists('articles','title'));

        $migration = new MockMigration($this->getAdapter(), ['datasource'=>'test']);
        $migration->removeIndex('articles','title');
        $migration->start();
        $this->assertFalse($migration->indexExists('articles','title'));

        $migration->rollback();
        $this->assertTrue($migration->indexExists('articles','title'));

    }
    public function testRemoveForeignKey(){
        $migration = new MockMigration($this->getAdapter(), ['datasource'=>'test']);
        $migration->addColumn('articles', 'user_id', 'integer', ['default'=>1000]);
        $migration->addForeignKey('articles', 'users');
        $migration->addForeignKey('articles','users',['column'=>'author_id','name'=>'fk_a1']);
        $migration->start();
        $this->assertTrue($migration->foreignKeyExists('articles','user_id'));
        $this->assertTrue($migration->foreignKeyExists('articles',['name'=>'fk_a1']));

        $migration = new MockMigration($this->getAdapter(), ['datasource'=>'test']);
        $migration->removeForeignKey('articles','users');
        $migration->removeForeignKey('articles',['name'=>'fk_a1']);
        $migration->start();
        $this->assertFalse($migration->foreignKeyExists('articles', 'user_id'));
        $this->assertFalse($migration->foreignKeyExists('articles', ['name'=>'fk_a1']));
        $migration->rollback();
        $this->assertTrue($migration->foreignKeyExists('articles','user_id'));
        $this->assertTrue($migration->foreignKeyExists('articles', ['name'=>'fk_a1']));

    }
}
