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

 /**
  * Migrations - This is designed for editing the schema, sometimes data might need to modified but
  * it should not be used to insert data. (if you have too then use connection manager)
  *
  */
namespace Origin\Migration;

use Origin\Model\ConnectionManager;
use Origin\Exception\Exception;
use Origin\Migration\Driver\MySQLDriver;
use Origin\Core\Inflector;
use Origin\Exception\InvalidArgumentException;
use Origin\Model\Exception\DatasourceException;

class Migration
{
    /**
     * Datasource used
     *
     * @var string
     */
    protected $datasource = null;

    /**
     * The SQL statements that will be executed
     *
     * @var array
     */
    protected $statements = [];
    
    /**
     * The SQL statements for UPs
     *
     * @var array
     */
    protected $upStatements = [];

    /**
     * The SQL statements for down UPs
     *
     * @var array
     */
    protected $downStatements = [];

    /**
     * The database adapter
     *
     * @var \Origin\Migration\Adapter\MysqlAdapter
     */
    protected $adapter = null;

    public function __construct(Adapter $adapter, array $options=[])
    {
        $options += ['datasource'=>'default'];
        $this->adapter = $adapter;
        $this->adapter->datasource = $options['datasource']; // think about this
        $this->datasource = $options['datasource'];
    }


    /**
     * Migration code here will be automatically reversed (except execute)
     *
     * @return void
     */
    public function change()
    {
    }

    /**
     * This will be called to undo changes from when using change, place
     * code here that cannot be automatically reversed.
     *
     * @return void
     */
    public function reversable(){

    }

    /**
     * This called when migrating up
     *
     * @return void
     */
    public function up(){

    }

    /**
     * This is called when migrating down
     *
     * @return void
     */
    public function down(){

    }



    /**
     * Migrates up and returns the statments executed
     *
     * @return array $statements
     */
    public function start() : array
    {
        $this->up();
        $this->change();
        if (empty($this->upStatements)) {
            throw new Exception('Migration change does not do anything.');
        }
        $this->executeStatements($this->upStatements);
        return $this->upStatements;
    }

    /**
     * Migrates down and returns the statments executed. It will runs the automatically reversable
     * changes, the 
     *
     * @return array $statements
     */
    public function rollback() : array
    {
        $this->down();
        $this->change();
        $this->reversable();
        if (empty($this->downStatements)) {
            throw new Exception('Migration change does not do anything.');
        }
        $statements = array_reverse($this->downStatements); // cant reverse normal
        $this->executeStatements($statements);
        return $statements;
    }

      /**
     * Runs the migration statements
     *
     * @param array $statements
     * @return void
     */
    private function executeStatements(array $statements)
    {
        $this->connection()->begin();
        foreach ($statements as $statement) {
            try {
                $this->connection()->execute($statement);
            } catch (DatasourceException $ex) {
                $this->connection()->rollback();
                throw new Exception($ex->getMessage());
            }
        }
        $this->connection()->commit();
    }

    /**
     * Returns the connection
     *
     * @return \Origin\Model\Datasource;
     */
    public function connection()
    {
        return ConnectionManager::get($this->datasource);
    }

    /**
     * Returns the database adapter
     *
     * @return \Origin\Migration\Adapter\MysqlAdapter
     */
    public function adapter()
    {
        return $this->adapter;
    }
    /**
     * This executes an SQL statement (cannot be reversed)
     *
     * @param string $sql
     * @return string
     */
    public function execute(string $sql)
    {
        return $this->upStatements[] = $sql;
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
     * @parama options The option keys are as follows
     *   - id: default true wether to create primaryKey Column
     *   - primaryKey: default is 'id' the column name of the primary key
     *   - options: extra options to be appended to the definition
     * @return void
     */
    public function createTable(string $name, array $schema=[], array $options = [])
    {
        $options += ['id'=>true,'primaryKey'=>'id'];
        if ($options['id']) {
            $schema = array_merge([$options['primaryKey']=>'primaryKey'], $schema);
        }
      
        $this->upStatements[] =  $this->adapter()->createTable($name, $schema,$options);
        $this->downStatements[] = $this->adapter()->dropTable($name);
    }

    /**
     * Creates a join table for has and belongsToMany
     *
     * @param string $table1
     * @param string $table2
     * @param array $options same options as create table
     * @return void
     */
    public function createJoinTable(string $table1, string $table2, array $options=[])
    {
        $options += ['id'=>false];
        $tables =  [$table1,$table2 ];
        sort($tables);
        $tableName = implode('_', $tables);
        # This will create up and down
        $this->createTable($tableName, [
            Inflector::singularize($tables[0]).'_id' => 'integer',
            Inflector::singularize($tables[1]).'_id'=> 'integer',
        ], $options);
    }


   
    /**
     * Drops a table from database
     *
     * @param string $table
     * @return void
     */
    public function dropTable(string $table)
    {
        $this->upStatements[] = $this->adapter()->dropTable($table);
        $schema = $this->adapter()->schema($table);
        $this->downStatements[] = $this->adapter()->createTable($table, $schema);
    }

    /**
     * Renames a table
     *
     * @param string $from
     * @param string $to
     * @return void
     */
    public function renameTable(string $from, string $to)
    {
        $this->upStatements[] = $this->adapter()->renameTable($from, $to);
        $this->downStatements[] = $this->adapter()->renameTable($to, $from);
    }

  /**
     * Returns an list of tables
     *
     * @return array
     */
    public function tables()
    {
        return $this->adapter()->tables();
    }

    /**
     * Checks if a table exists
     *
     * @param string $name
     * @return void
     */
    public function tableExists(string $name)
    {
        return $this->adapter()->tableExists($name);
    }

    /**
     * Adds a new column name of the type to the table
     *
     * @param string $table table name
     * @param string $name column name
     * @param string $type (primaryKey,string,text,integer,bigint,float,decimal,datetime,time,date,binary,boolean)
     * @param array $options The following options keys can be used:
     *   - limit: limits the column length for string and bytes for text,binary,and integer
     *   - default: the default value, use '' or nill for null
     *   - null: allows or disallows null values to be used
     *   - precision: the precision for the number (places to before the decimal point)
     *   - scale: the numbers after the decimal point
     * @return void
     */
    public function addColumn(string $table, string $name, string $type, array $options=[])
    {
        $this->upStatements[] = $this->adapter()->addColumn($table, $name, $type, $options);
        $this->downStatements[] = $this->adapter()->removeColumn($table, $name);
    }

     /**
     * Changes a column according to the new definition
     * @param string $table table name
     * @param string $name column name
     * @param string $table table name
     * @param string $name column name
     * @param string $type (primaryKey,string,text,integer,bigint,float,decimal,datetime,time,date,binary,boolean)
     * @param array $options The following options keys can be used:
     *   - limit: limits the column length for string and bytes for text,binary,and integer
     *   - default: the default value, use '' or nill for null
     *   - null: allows or disallows null values to be used
     *   - precision: the precision for the number (places to before the decimal point)
     *   - scale: the numbers after the decimal point
     */
    public function changeColumn(string $table, string $name, string $type, array $options=[])
    {
        $schema = $this->adapter()->schema($table);
        if (empty($schema[$name])) {
            throw new Exception('Column does not exist in the table');
        }
        
        $this->upStatements[] = $this->adapter()->changeColumn($table, $name, $type, $options);
        $options = $schema[$name];
        $this->downStatements[] = $this->adapter()->changeColumn($table, $name, $options['type'], $options);
    }

    /**
     * Renames a column name
     *
     * @param string $table
     * @param string $from
     * @param string $to
     * @return void
     */
    public function renameColumn(string $table, string $from, string $to)
    {
        $this->upStatements[] = $this->adapter()->renameColumn($table, $from, $to);
        $this->downStatements[] = $this->adapter()->renameColumn($table, $to, $from);
    }

    /**
     * Removes a column from the table§
     *
     * @param string $table
     * @param string $column
     * @return void
     */
    public function removeColumn(string $table, string $column)
    {
        $this->upStatements[] = $this->adapter()->removeColumn($table, $column);

        $schema = $this->adapter()->schema($table);
        $this->downStatements[] = $this->adapter()->addColumn($table, $column, $schema[$column]['type'], $schema[$column]);
    }

    /**
     * Removes multiple columns from the table
     *
     * @param string $table
     * @param array $columns
     * @return void
     */
    public function removeColumns(string $table, array $columns)
    {
        $this->upStatements[] = $this->adapter()->removeColumns($table, $columns);
        $schema = $this->adapter()->schema($table);

        foreach ($columns as $column) {
            if (isset($schema[$column])) {
                $this->downStatements[] = $this->adapter()->addColumn($table, $column, $schema[$column]['type'], $schema[$column]);
            }
        }
    }


    /**
     * Returns an array of columns
     *
     * @param string $table
     * @return array
     */
    public function columns(string $table)
    {
        return $this->adapter()->columns($table);
    }

    /**
     * Checks if a column exists in a table
     *
     * @param string $table
     * @param string $column
     * @param string $options
     *  - type: type of field
     *  - default: if default true or false
     *  - null: if null values allowed
     *  - precision: value
     *  - limit: value
     * @return bool
     */
    public function columnExists(string $table, string $column, array $options =[])
    {
        if ($options) {
            $schema = $this->adapter()->schema($table);
  
            if (!isset($schema[$column])) {
                return false;
            }
         
            if (isset($options['type']) and $schema[$column]['type'] !== $options['type']) {
                return false;
            }

            if (isset($options['default']) and $schema[$column]['default'] !== $options['default']) {
                return false;
            }
            if (isset($options['limit']) and isset($schema[$column]['limit']) and (int) $schema[$column]['limit'] !== (int) $options['limit']) {
                return false;
            }
            if (isset($options['precision']) and isset($schema[$column]['precision']) and (int) $schema[$column]['precision'] !== (int) $options['precision']) {
                return false;
            }
            if (isset($options['scale']) and isset($schema[$column]['scale']) and (int) $schema[$column]['scale'] !== (int) $options['scale']) {
                return false;
            }
            return true;
        }
        return $this->adapter()->columnExists($table, $column);
    }



    /**
      * Add an index on table
      *
      * @param string $table
      * @param string|array $column owner_id, [owner_id,tenant_id]
      * @param array $options
      *  - name: name of index
      */
      public function addIndex(string $table, $column, array $options=[])
      {
          $options += ['unique'=>false,'name'=>null];
         
          $options = $this->indexOptions($table,array_merge(['column'=>$column],$options));
          $columnString = $options['column'];
          if(is_array($columnString)){
              $columnString  = implode(',',$columnString);
          }
      
          $this->upStatements[] = $this->adapter()->addIndex($table, $columnString ,$options['name'], $options);
          $this->downStatements[] = $this->adapter()->removeIndex($table,$options['name']);
      }
  
      /**
       * Removes an index on table if it exists
       *
       * @param string $table
       * @param string|array $options owner_id, [owner_id,tenant_id] or ['name'=>'index_name']
       * @return string
       */
      public function removeIndex(string $table, $options)
      {
          $options = $this->indexOptions($table,$options);
  
          $index = null;
          foreach($this->indexes($table) as $index){
              if($index['name'] === $options['name']){
                  break;
              }
              $index = null;
          }
      
          if($this->indexNameExists($table,$options['name'])){
              $this->upStatements[] = $this->adapter()->removeIndex($table, $options['name']);
              $this->downStatements[] = $this->adapter()->addIndex(
                  $table, $options['column'],$options['name'],['unique'=>$index['unique']]
              );
          }
      }
  
      /**
       * Preps index options
       *
       * @param string $table
       * @param string|array $options 
       * @return array
       */
      private function indexOptions(string $table,$options) : array
      {
          if(is_string($options) OR (!isset($options['name']) AND !isset($options['column']))){
              $options = ['column' => $options];
          }
          if(!empty($options['column'])){
              $options['name'] = $this->getIndexName($table,$options['column']);
          }
          return $options;
      }
  
      /**
       * Renames an index on a table
       *
       * @param string $table
       * @param string $oldName name_of_index
       * @param string $newName new_name_of_index
       * @return void
       */
      public function renameIndex(string $table, string $oldName, string $newName)
      {
          $this->upStatements[] = $this->adapter()->renameIndex($table, $oldName, $newName);
          $this->downStatements[] = $this->adapter()->renameIndex($table, $newName, $oldName);
      }
  
 
    /**
     * Checks if a table exists
     *
     * @param string $name
     * @return void
     */
    public function indexes(string $table)
    {
        return $this->adapter()->indexes($table);
    }

  /**
     * Checks if an index exists
     * @param string $table
     * @param string|array $column owner_id, [owner_id,tenant_id]
     * @param array $options
     *  - name: name of index
     *  - unique: default false bool
     * @return bool
     */
    public function indexExists(string $table, $options)
    {
        $options = $this->indexOptions($table,$options);
        return $this->indexNameExists($table,$options['name']);
    }

    /**
     * Checks if an index exists
     *
     * @param string $table
     * @param string $indexName table_column_name_index
     * @return bool
     */
    public function indexNameExists(string $table,string $indexName):bool
    {
        $indexes = $this->indexes($table);
        foreach($indexes as $index){
            if($index['name'] === $indexName){
                return true;
            }
        }
        return false;
    }

    /**
     * Gets an index name
     *
     * @param string $table
     * @param string|array $column , [column_1,column_2]
     * @return string table_column_name_index
     */
    private function getIndexName(string $table,$column) : string
    {
        $name = implode('_',(array) $column);
        return strtolower($table . '_' . $name ) .'_index';
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
      public function addForeignKey(string $fromTable, string $toTable, array $options=[])
      {
          $options += [
              'column' => strtolower(Inflector::singularize($toTable)).'_id',
              'primaryKey' => 'id',
              'name' => null
          ];
  
          // Get column name first
          if ($options['name'] === null) {
              $options['name'] = 'fk_origin_' . $this->getForeignKeyIdentifier($fromTable, $options['column']);
          }
         
          $this->upStatements[] = $this->adapter()->addForeignKey($fromTable, $toTable, $options);
          $this->downStatements[] = $this->adapter()->removeForeignKey($fromTable, $options['name']);
      }
  
      /**
       * removeForeignKey('accounts','owners'); // removes accounts.owner_id
       * removeForeignKey('accounts',['column'=>'owner_id'];
       * removeForeignKey('accounts',['name'=>'fk_origin_1234567891'];
       *
       * @param string $fromTable
       * @param string|array $optionsOrToTable table name or array with any of the following options:
       *  - column: the foreignKey on the fromTable defaults optionsOrToTable.singularized_id
       *  - name: the constraint name defaults to fk_origin_1234567891
       *  - primaryKey: the primary key defaults to id
       * @return void
       */
      public function removeForeignKey(string $fromTable, $optionsOrToTable)
      {
          $options = $optionsOrToTable;
          if (is_string( $options)) {
              $options = [
                  'column' => strtolower(Inflector::singularize($options)).'_id',
              ];
          }
          $options += ['name'=> null,'column'=>null,'primaryKey' => 'id'];
  
          if (empty($options['column']) and empty($options['name'])) {
              throw new InvalidArgumentException('Column or name needs to be specified');
          }
  
          $foreignKey = null;
          $foreignKeys = $this->foreignKeys($fromTable);
          
          foreach ($foreignKeys as $foreignKey) {
              if ( $options['column'] AND $foreignKey['column_name'] === $options['column']) {
                  $options['name'] = $foreignKey['constraint_name'];
                  break;
              }
              if ( $options['name'] AND $foreignKey['constraint_name'] === $options['name']) {
                  $options['column'] = $foreignKey['column_name'];
                  break;
              }
              $foreignKey = null;
          }
        
          if($foreignKey){
              $this->upStatements[] = $this->adapter()->removeForeignKey($fromTable, $options['name']);
              $this->downStatements[] = $this->adapter()->addForeignKey($fromTable,$foreignKey['referenced_table_name'],$options);
          }
      }
  
      public function foreignKeys(string $table)
      {
          return $this->adapter()->foreignKeys($table);
      }
  
      /**
       * Checks if foreignKey exists
       * @param string $fromTable
       * @param string|array $optionsOrToTable either table name e.g. users or array of options
       *  - column: column name
       *  - name: foreignkey name
       * @return void
       */
      /**
       * Undocumented function
       *
       * @param string $fromTable
       * @param string|array $columnOrOptions column name or array 
       *  - name: foreignkey name
       */
      public function foreignKeyExists(string $fromTable, $columnOrOptions)
      {
  
          // Its a table
          if (is_string($columnOrOptions)) {
              $columnOrOptions = ['column'=>$columnOrOptions];
          }
       
          return $this->adapter()->foreignKeyExists($fromTable, $columnOrOptions);
      }
      
    /**
    * Creates a unique foreignKeyName
    *
    * @param string $table
    * @param string $column
    * @return string
    */
    private function getForeignKeyIdentifier(string $table, string $column) : string
    {
        return hash('crc32', $table . '__' . $column);
    }

 /**
     * Fetchs a single row from the database
     *
     * @param string $sql
     * @return array|null
     */
    public function fetchRow(string $sql)
    {
        return $this->adapter()->fetchRow($sql);
    }

    /**
    * Fetchs all rows from database
    *
    * @param string $sql
    * @return array
    */
    public function fetchAll(string $sql)
    {
        return  $this->adapter()->fetchAll($sql);
    }

    
    /**
     * Returns the SQL statements generate from the change
     *
     * @return array
     */
    public function statements() : array
    {
        return ['up'=>$this->upStatements,'down'=>$this->downStatements];
    }

}
