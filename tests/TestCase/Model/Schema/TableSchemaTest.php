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

use Origin\Core\Exception\Exception;
use Origin\Model\Schema\TableSchema;
use Origin\TestSuite\OriginTestCase;

class TableSchemaTest extends OriginTestCase
{
    public function testAddColumn()
    {
        $schema = new TableSchema('posts');
        $result = $schema->addColumn('id', [
            'type' => 'integer',
        ]);
        $this->assertInstanceOf(TableSchema::class, $result);
        
        $this->assertNotEmpty($schema->columns());
        $schema->addColumn('name', 'string');
        $this->assertEquals(['name' => 'id','type' => 'integer'], $schema->columns('id'));
        $this->assertEquals(['name' => 'name','type' => 'string'], $schema->columns('name'));
    }

    public function testAddIndex()
    {
        $columns = [
            'id' => 'integer',
            'name' => 'string',
            'created' => 'datetime',
        ];
        $schema = new TableSchema('posts', $columns);
      
        $result = $schema->addIndex('index_1', 'name');
        $this->assertInstanceOf(TableSchema::class, $result);
     
        $this->assertNotEmpty($schema->indexes());
        $this->assertEquals(['table' => 'posts','type' => 'index','column' => ['name'],'name' => 'index_1'], $schema->indexes('index_1'));

        $this->expectException(Exception::class);
        $schema->addIndex('abc', ['type' => 'index']);
    }

    public function testAddConstraintPrimaryKey()
    {
        $columns = [
            'id' => 'integer',
            'name' => 'string',
            'created' => 'datetime',
        ];
        $schema = new TableSchema('posts', $columns);
        $result = $schema->addConstraint('primary', ['type' => 'primary','column' => 'id']);
        $this->assertInstanceOf(TableSchema::class, $result);

        $this->assertNotEmpty($schema->constraints());
        $expected = ['name' => 'primary','type' => 'primary','column' => 'id'];
        $this->assertEquals($expected, $schema->constraints('primary'));
        $this->assertEquals('id', $schema->primaryKey());
    }

    public function testAddConstraintInvalidTypeException()
    {
        $columns = [
            'id' => 'integer',
            'name' => 'string',
            'created' => 'datetime',
        ];
        $schema = new TableSchema('posts', $columns);
        $this->expectException(Exception::class);
        $schema->addConstraint('name', ['type' => 'fozzy-wuzzy','column' => ['id']]);
    }

    public function testAddConstraintMissingColumnsException()
    {
        $columns = [
            'id' => 'integer',
            'name' => 'string',
            'created' => 'datetime',
        ];
        $schema = new TableSchema('posts', $columns);
        $this->expectException(Exception::class);
        $schema->addConstraint('name', ['type' => 'primary']);
    }

    public function testAddConstraintForeignKey()
    {
        $columns = [
            'id' => 'integer',
            'owner_id' => 'integer',
            'name' => 'string',
            'created' => 'datetime',
        ];
        $schema = new TableSchema('posts', $columns);
        $schema->addConstraint('fk_users_id', ['type' => 'foreign','column' => ['owner_id'],'references' => ['users','id']]);
        
        $this->assertNotEmpty($schema->constraints());
        $expected = ['type' => 'foreign','column' => ['owner_id'],'references' => ['users','id'],'table' => 'posts','name' => 'fk_users_id'];
        $this->assertEquals($expected, $schema->constraints('fk_users_id'));

        $schema = new TableSchema('posts', $columns);
        $schema->addConstraint('fk_users_id', ['type' => 'foreign','column' => ['owner_id'],'references' => ['users','id'],'update' => 'cascade','delete' => 'setNull']);
        $constraint = $schema->constraints('fk_users_id');
        $this->assertEquals('cascade', $constraint['update']);
        $this->assertEquals('setNull', $constraint['delete']);
    }

    public function testAddConstraintMissingReferencesException()
    {
        $columns = [
            'id' => 'integer',
            'name' => 'string',
            'created' => 'datetime',
        ];
        $schema = new TableSchema('posts', $columns);
        $this->expectException(Exception::class);
        $schema->addConstraint('name', ['type' => 'foreign','column' => ['id']]);
    }

    public function testAddConstraintInvalidUpdateReferenceExpection()
    {
        $columns = [
            'id' => 'integer',
            'name' => 'string',
            'created' => 'datetime',
        ];
        $schema = new TableSchema('posts', $columns);
        $this->expectException(Exception::class);
        $schema->addConstraint('name', ['type' => 'foreign','column' => ['id'],'references' => ['users','id'],'update' => 'foo']);
    }

    public function testAddConstraintInvalidReferencesException()
    {
        $columns = [
            'id' => 'integer',
            'name' => 'string',
            'created' => 'datetime',
        ];
        $schema = new TableSchema('posts', $columns);
        $this->expectException(Exception::class);
        $schema->addConstraint('fk_users_id', ['type' => 'foreign','column' => ['owner_id'],'references' => ['id']]);
    }

    public function testAddConstraintInvalidDeleteReference()
    {
        $columns = [
            'id' => 'integer',
            'name' => 'string',
            'created' => 'datetime',
        ];
        $schema = new TableSchema('posts', $columns);
        $this->expectException(Exception::class);
        $schema->addConstraint('name', ['type' => 'foreign','column' => ['id'],'references' => ['users','id'],'delete' => 'foo']);
    }

    public function testOptions()
    {
        $columns = [
            'id' => 'integer',
            'name' => 'string',
            'created' => 'datetime',
        ];
        $schema = new TableSchema('posts', $columns);
        $options = ['engine' => 'InnoDB','collate' => 'utf8_unicode_ci'];
        $schema->options($options);
        $this->assertEquals($options, $schema->options());
    }
}
