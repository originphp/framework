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
  * @internal Originaly wanted change to automatically reverse changes, some changes to be reversed need
  * actual schema, which means data needs to be stored somewhere.
  *
  */
namespace Origin\Migration;

use Origin\Core\Inflector;
use Origin\Exception\Exception;
use Origin\Model\ConnectionManager;
use Origin\Model\Schema\BaseSchema;
use Origin\Exception\InvalidArgumentException;
use Origin\Model\Exception\DatasourceException;
use Origin\Migration\Exception\IrreversibleMigrationException;

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
     * These are the statements magically detected to reverse
     * @internal only works when you run the migration first time, rollback wont work since some schema information wont
     * be available anymore
     * @var array
     */
    protected $reverseStatements = [];

    /**
     * The database adapter
     *
     * @var \Origin\Model\Schema\BaseSchema
     */
    protected $adapter = null;

    /**
     * holding info
     *
     * @var array
     */
    protected $pendingTables = [];
    protected $pendingColumns = [];

    /**
     * Constructor
     *
     * @param \Origin\Model\Schema\BaseSchema $adapter
     */
    public function __construct(BaseSchema $adapter)
    {
        $this->adapter = $adapter;
        $this->datasource = $adapter->datasource();
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
    public function reversable()
    {
    }

    /**
     * This called when migrating up
     *
     * @return void
     */
    public function up()
    {
    }

    /**
     * This is called when migrating down
     *
     * @return void
     */
    public function down()
    {
    }

    /**
     * Migrates up and returns the statments executed
     *
     * @return array $statements
     */
    public function start() : array
    {
        $this->up();
        $this->change(); // only run in start

        if (empty($this->statements)) {
            throw new Exception('Migration does not do anything.');
        }
        $this->executeStatements($this->statements);

        return $this->statements;
    }

    /**
     * Migrates down and returns the statments executed.
     * @param array $statements - additional statement
     * @return array $statements
     */
    public function rollback($statements = []) : array
    {
        $this->down();
        $this->reversable(); // Do not run change here
        $statements = array_merge($this->statements, $statements);
        if (empty($statements)) {
            throw new Exception('Migration does not do anything.');
        }
   
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
     * @return \Origin\Model\Schema\BaseSchema
     */
    public function adapter()
    {
        return $this->adapter;
    }
    /**
     * Executes a raw SQL statement, can only be run from up or down
     *
     * @param string $sql
     * @return string
     */
    public function execute(string $sql)
    {
        if (! in_array($this->calledBy(), ['up','down'])) {
            throw new Exception('Execute can only be called from up/down');
        }
        $this->statements[] = $sql;
    }

    /**
     * Finds out which function called the function
     *
     * @return void
     */
    public function calledBy()
    {
        $result = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 3);
        $calledBy = null;
        if (isset($result[2]['function'])) {
            $calledBy = $result[2]['function'];
        }

        return $calledBy;
    }

    /**
     * Use this to tell Down that the migration cannot be reversed such as
     * when deleting data.
     *
     * @return void
     */
    public function throwIrreversibleMigrationException()
    {
        throw new IrreversibleMigrationException('Irreversible Migration');
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
    public function createTable(string $name, array $schema = [], array $options = [])
    {
        $options += ['id' => true,'primaryKey' => 'id'];
        if ($options['id']) {
            $schema = array_merge([$options['primaryKey'] => 'primaryKey'], $schema);
        }
        
        # For the benefit of working with Indexs and Foreign Keys  on new tables/columns
        $this->pendingTables[] = $name;
        $this->pendingColumns[$name] = array_keys($schema);
  
        $this->statements[] = $this->adapter()->createTable($name, $schema, $options);
       
        if ($this->calledBy() === 'change') {
            $this->reverseStatements[] = $this->adapter()->dropTable($name);
        }
    }

    /**
     * Creates a join table for has and belongsToMany
     *
     * @param string $table1
     * @param string $table2
     * @param array $options same options as create table
     * @return void
     */
    public function createJoinTable(string $table1, string $table2, array $options = [])
    {
        $options += ['id' => false,'primaryKey' => false];
        $tables = [$table1,$table2];
        sort($tables);
        $tableName = implode('_', $tables);
        # This will create up and down
        $schema = [
            Inflector::singularize($tables[0]).'_id' => 'integer',
            Inflector::singularize($tables[1]).'_id' => 'integer',
        ];

        # For the benefit of working with Indexs and Foreign Keys  on new tables/columns
        $this->pendingTables[] = $tableName ;
        $this->pendingColumns[$tableName] = array_keys($schema);

        $this->statements[] = $this->adapter()->createTable($tableName, $schema, $options);

        if ($this->calledBy() === 'change') {
            $this->reverseStatements[] = $this->adapter()->dropTable($tableName);
        }
    }

    /**
     * Drops a table from database
     *
     * @param string $table
     * @param array $options Options are:
     *   - ifExists: default false
     * @return void
     */
    public function dropTable(string $table, array $options = [])
    {
        if (! $this->tableExists($table)) {
            throw new Exception("{$table} table does not exist");
        }

        $this->statements[] = $this->adapter()->dropTable($table, $options);

        if ($this->calledBy() === 'change') {
            $schema = $this->adapter()->schema($table);
            $this->reverseStatements[] = $this->adapter()->createTable($table, $schema);
        }
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
        $this->statements[] = $this->adapter()->renameTable($from, $to);
        if ($this->calledBy() === 'change') {
            $this->reverseStatements[] = $this->adapter()->renameTable($to, $from);
        }
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
        return in_array($name, $this->tables());
    }

    /**
     * Adds a new column name of the type to the table
     *
     * @param string $table table name
     * @param string $name column name
     * @param string $type (primaryKey,string,text,integer,bigint,float,decimal,datetime,time,date,binary,boolean)
     * @param array $options The following options keys can be used:
     *   - limit: limits the column length for string and bytes for text,binary,and integer
     *   - default: the default value, use '' or null
     *   - null: allows or disallows null values to be used
     *   - precision: the precision for the number (places to before the decimal point)
     *   - scale: the numbers after the decimal point
     * @return void
     */
    public function addColumn(string $table, string $name, string $type, array $options = [])
    {
        if (! $this->tableExists($table)) {
            throw new Exception("{$table} table does not exist");
        }

        # For the benefit of working with Indexs and Foreign Keys  on new tables/columns
        $this->pendingColumns[$table][] = $name;

        $this->statements[] = $this->adapter()->addColumn($table, $name, $type, $options);
        if ($this->calledBy() === 'change') {
            $this->reverseStatements[] = $this->adapter()->removeColumn($table, $name);
        }
    }

    /**
    * Changes a column according to the new definition
    *
    * @internal pgsql works differently to mysql. In mysql whole column is redefined, and null value is not
    * constraint.
    * @param string $table table name
    * @param string $name column name
    * @param string $table table name
    * @param string $name column name
    * @param string $type (primaryKey,string,text,integer,bigint,float,decimal,datetime,time,date,binary,boolean)
    * @param array $options The following options keys can be used:
    *   - limit: limits the column length for string and bytes for text,binary,and integer
    *   - default: the default value, use '' or null
    *   - null: allows or disallows null values to be used
    *   - precision: the precision for the number (places to before the decimal point)
    *   - scale: the numbers after the decimal point
    */
    public function changeColumn(string $table, string $name, string $type, array $options = [])
    {
        if (! $this->tableExists($table)) {
            throw new Exception("{$table} table does not exist");
        }

        if (! $this->columnExists($table, $name)) {
            throw new Exception("{$name} does not exist in the {$table}");
        }

        $schema = $this->adapter()->schema($table);
        $engine = $this->connection()->engine();
       
        // Drop DEFAULT constraint if it exists (same in both MySQL and PgSQL)
        if (in_array($engine, ['pgsql','mysql']) and $schema[$name]['default']) {
            $this->statements[] = "ALTER TABLE {$table} ALTER COLUMN {$name} DROP DEFAULT";
        }

        // In PgSQL not null is constraint.
        if ($engine === 'pgsql' and $schema[$name]['null'] === false) {
            $this->statements[] = "ALTER TABLE {$table} ALTER COLUMN {$name} DROP NOT NULL";
        }
        
        $this->statements[] = $this->adapter()->changeColumn($table, $name, $type, $options);

        if ($this->calledBy() === 'change') {
            $options = $schema[$name];
            $this->reverseStatements[] = $this->adapter()->changeColumn($table, $name, $options['type'], $options);
        }
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
        if (! $this->tableExists($table)) {
            throw new Exception("{$table} table does not exist");
        }

        if (! $this->columnExists($table, $from)) {
            throw new Exception("{$from} column does not exist in the {$table}");
        }
       
        # For the benefit of working with Indexs and Foreign Keys  on new tables/columns
        $this->pendingColumns[$table][] = $to;

        $this->statements[] = $this->adapter()->renameColumn($table, $from, $to);
        if ($this->calledBy() === 'change') {
            $this->reverseStatements[] = $this->adapter()->renameColumn($table, $to, $from);
        }
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
        if (! $this->tableExists($table)) {
            throw new Exception("{$table} table does not exist");
        }

        if (! $this->columnExists($table, $column)) {
            throw new Exception("{$column} does not exist in the {$table}");
        }

        $schema = $this->adapter()->schema($table);
        $this->statements[] = $this->adapter()->removeColumn($table, $column);
        if ($this->calledBy() === 'change') {
            $this->reverseStatements[] = $this->adapter()->addColumn($table, $column, $schema[$column]['type'], $schema[$column]);
        }
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
        if (! $this->tableExists($table)) {
            throw new Exception("{$table} table does not exist");
        }
        foreach ($columns as $column) {
            if (! $this->columnExists($table, $column)) {
                throw new Exception("{$column} does not exist in the {$table}");
            }
        }
        $schema = $this->adapter()->schema($table);

        $this->statements[] = $this->adapter()->removeColumns($table, $columns);
        foreach ($columns as $column) {
            $this->reverseStatements[] = $this->adapter()->addColumn($table, $column, $schema[$column]['type'], $schema[$column]);
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
    public function columnExists(string $table, string $column, array $options = [])
    {
        if (! $this->tableExists($table)) {
            throw new Exception("{$table} table does not exist");
        }
       
        $schema = $this->adapter()->schema($table);

        if (! isset($schema[$column])) {
            return false;
        }
        if (isset($options['type']) and $schema[$column]['type'] !== $options['type']) {
            return false;
        }

        if (array_key_exists('default', $options) and $schema[$column]['default'] !== $options['default']) {
            return false;
        }

        if (array_key_exists('null', $options) and $schema[$column]['null'] !== $options['null']) {
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

    /**
      * Add an index on table
      *
      * @param string $table
      * @param string|array $column owner_id, [owner_id,tenant_id]
      * @param array $options
      *  - name: name of index
      */
    public function addIndex(string $table, $column, array $options = [])
    {
        if (! $this->tableExists($table) and ! in_array($table, $this->pendingTables)) {
            throw new Exception("{$table} table does not exist");
        }
        $options += ['unique' => false,'name' => null];
         
        $options = $this->indexOptions($table, array_merge(['column' => $column], $options));
        $columnString = $options['column'];
        if (is_array($columnString)) {
            $columnString = implode(',', $columnString);
        }
      
        $this->statements[] = $this->adapter()->addIndex($table, $columnString, $options['name'], $options);
        if ($this->calledBy() === 'change') {
            $this->reverseStatements[] = $this->adapter()->removeIndex($table, $options['name']);
        }
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
        if (! $this->tableExists($table)) {
            throw new Exception("{$table} table does not exist");
        }

        $options = $this->indexOptions($table, $options);
  
        $index = null;
        foreach ($this->indexes($table) as $index) {
            if ($index['name'] === $options['name']) {
                break;
            }
            $index = null;
        }
        $this->statements[] = $this->adapter()->removeIndex($table, $options['name']);

        if ($this->indexNameExists($table, $options['name'])) {
            if ($this->calledBy() === 'change') {
                $this->reverseStatements[] = $this->adapter()->addIndex(
                    $table,
                    $options['column'],
                    $options['name'],
                    ['unique' => $index['unique']]
                    );
            }
        }
    }
  
    /**
     * Preps index options
     *
     * @param string $table
     * @param string|array $options
     * @return array
     */
    private function indexOptions(string $table, $options) : array
    {
        if (is_string($options) or (! isset($options['name']) and ! isset($options['column']))) {
            $options = ['column' => $options];
        }
        if (! empty($options['column'])) {
            $options['name'] = $this->getIndexName($table, $options['column']);
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
        $this->statements[] = $this->adapter()->renameIndex($table, $oldName, $newName);
        if ($this->calledBy() === 'change') {
            $this->reverseStatements[] = $this->adapter()->renameIndex($table, $newName, $oldName);
        }
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
        $options = $this->indexOptions($table, $options);

        return $this->indexNameExists($table, $options['name']);
    }

    /**
     * Checks if an index exists
     *
     * @param string $table
     * @param string $indexName table_column_name_index
     * @return bool
     */
    public function indexNameExists(string $table, string $indexName):bool
    {
        $indexes = $this->indexes($table);
        foreach ($indexes as $index) {
            if ($index['name'] === $indexName) {
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
    private function getIndexName(string $table, $column) : string
    {
        $name = implode('_', (array) $column);

        return strtolower($table . '_' . $name) .'_index';
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
    public function addForeignKey(string $fromTable, string $toTable, array $options = [])
    {
        if (! $this->tableExists($fromTable) and ! in_array($fromTable, $this->pendingTables)) {
            throw new Exception("{$fromTable} does not exist");
        }

        if (! $this->tableExists($toTable) and ! in_array($toTable, $this->pendingTables)) {
            throw new Exception("{$toTable} does not exist");
        }

        $options += [
            'column' => strtolower(Inflector::singularize($toTable)).'_id',
            'primaryKey' => 'id',
            'name' => null,
        ];
  
        // Get column name first
        if ($options['name'] === null) {
            $options['name'] = 'fk_origin_' . $this->getForeignKeyIdentifier($fromTable, $options['column']);
        }
         
        $this->statements[] = $this->adapter()->addForeignKey($fromTable, $toTable, $options);
        if ($this->calledBy() === 'change') {
            $this->reverseStatements[] = $this->adapter()->removeForeignKey($fromTable, $options['name']);
        }
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
        if (! $this->tableExists($fromTable)) {
            throw new Exception("{$fromTable} does not exist");
        }

        $options = $optionsOrToTable;
        if (is_string($options)) {
            $options = [
                'column' => strtolower(Inflector::singularize($options)).'_id',
            ];
        }
        $options += ['name' => null,'column' => null,'primaryKey' => 'id'];
  
        if (empty($options['column']) and empty($options['name'])) {
            throw new InvalidArgumentException('Column or name needs to be specified');
        }
  
        $foreignKey = null;
        $foreignKeys = $this->foreignKeys($fromTable);
          
        foreach ($foreignKeys as $foreignKey) {
            if ($options['column'] and $foreignKey['column_name'] === $options['column']) {
                $options['name'] = $foreignKey['constraint_name'];
                break;
            }
            if ($options['name'] and $foreignKey['constraint_name'] === $options['name']) {
                $options['column'] = $foreignKey['column_name'];
                break;
            }
            $foreignKey = null;
        }
        $this->statements[] = $this->adapter()->removeForeignKey($fromTable, $options['name']);

        if ($foreignKey) {
            if ($this->calledBy() === 'change') {
                $this->reverseStatements[] = $this->adapter()->addForeignKey($fromTable, $foreignKey['referenced_table_name'], $options);
            }
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
    public function foreignKeyExists(string $fromTable, $columnOrOptions)
    {
        if (! $this->tableExists($fromTable)) {
            throw new Exception("{$fromTable} does not exist");
        }

        // Its a table
        if (is_string($columnOrOptions)) {
            $columnOrOptions = ['column' => $columnOrOptions];
        }

        return $this->adapter()->foreignKeyExists($fromTable, $columnOrOptions);
    }
      
    /**
    * Creates a unique foreignKey name
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
    public function fetchRow(string $sql) : ?array
    {
        $ds = $this->connection();
        $result = null;
        if ($ds->execute($sql)) {
            $result = $ds->fetch();
        }

        return $result;
    }

    /**
    * Fetchs all rows from database
    *
    * @param string $sql
    * @return array
    */
    public function fetchAll(string $sql) : ?array
    {
        $ds = $this->connection();
        $result = null;
        if ($ds->execute($sql)) {
            $result = $ds->fetchAll();
        }

        return $result;
    }

    /**
     * Returns the SQL statements generate from the change
     *
     * @return array
     */
    public function statements() : array
    {
        return $this->statements;
    }

    /**
     * Returns the magically detected reverse statements.
     *
     * @return array
     */
    public function reverseStatements() : array
    {
        return array_reverse($this->reverseStatements);
    }
}
