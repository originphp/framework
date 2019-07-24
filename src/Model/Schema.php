<?php
use Origin\Migration\Migration;
use Origin\Model\Datasource;

class Schema
{
    /**
     * Holds the version number
     *
     * @var int
     */
    protected $version = null;

    /**
     * The Migration Object
     *
     * @var \Origin\Migration\Migration
     */
    protected $migration = null;

    /**
     * Defines the schema. Here add the statements
     * e.g
     *
     *  $this->createTable('posts',$fields);
     *  $this->addIndex('abc');
     *
     * @return void
     */
    public function define() : void
    {
    }

    /**
     * Returns the SQL for creating tables, indexes and foreign keys
     *
     * @param Datasource $datasource
     * @return array
     */
    public function createSql(Datasource $datasource) : array
    {
        $this->migration = new Migration($datasource->adapter());
        $this->define();
        return $this->migration->statements();
    }

    /**
     * Returns the SQL for dropping tables, indexes and foreign keys
     *
     * @param Datasource $datasource
     * @return array
     */
    public function dropSql(Datasource $datasource) : array
    {
        $this->migration = new Migration($datasource->adapter());
        $this->define();
        return $this->migration->reverseStatements();
    }

    /**
        * Creates a new table, the id column is created regardless.
        *
        * @example
        *
        * $this->createTable('products',[
        *  'name' => 'string',
        *  'amount' => ['type'=>'decimal,'limit'=>10,'precision'=>10],'
        * ]);
        *
        * @param string $name table name
        * @param array $schema This is an array to build the table, the key for each row should be the field name
        * and then pass either a string with type of field or an array with more specific options (type,limit,null,precision,default)
        * @param $options The option keys are as follows
        *   - id: default true wether to create primaryKey Column
        *   - primaryKey: default is 'id' the column name of the primary key
        *   - options: extra options to be appended to the definition
        * @return void
        */
    public function createTable(string $name, array $schema = [], array $options = []) : void
    {
        $this->migration->createTable($name, $schema, $options);
    }
    /**
          * Add an index on table
          *
          * @param string $table
          * @param string|array $column owner_id, [owner_id,tenant_id]
          * @param array $options
          *  - name: name of index
          */
    public function addIndex(string $table, $column, array $options = []) : void
    {
        $this->migration->addIndex($table, $column, $options);
    }

    /**
     * Adds a new foreignKey
     *
     * addForeignKey('articles','authors');
     * addForeignKey('articles','users',['column'=>'author_id','primaryKey'=>'lng_id']);
     *
     * @param string $fromTable e.g articles
     * @param string $toTable e.g. authors
     * @param array $options Options are:
     * - column: the foreignKey on the fromTable defaults toTable.singularized_id
     * - primaryKey: the primary key defaults to id
     * - name: the constraint name defaults to fk_origin_1234567891
     * @return void
     */
    public function addForeignKey(string $fromTable, string $toTable, array $options = []) : void
    {
        $this->migration->addForeignKey($fromTable, $toTable, $options);
    }
}
