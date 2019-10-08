<?php
declare(strict_types = 1);
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
 * @todo Eventually all functions that return sql statements name should end with SQL
 */
namespace Origin\Model\Schema;

use Origin\Model\Datasource;
use Origin\Model\ConnectionManager;

abstract class BaseSchema
{
    
    /**
     * Holds the datasource name
     *
     * @var string
     */
    protected $datasource = null;

    /**
     * Holds the column mapping information
     *
     * @var array
     */
    protected $columns = [];

    /**
     * The quote character for table names and columns
     *
     * @var string
     */
    protected $quote = '"';

    /**
     * Constructor
     *
     * @param string $datasource
     */
    public function __construct(string $datasource = 'default')
    {
        $this->datasource = $datasource;
    }

    abstract protected function columnSql(array $data);

    /**
     * This gets the createTable information from a table name
     *
     * @param string $table
     * @return string
     */
    abstract public function showCreateTable(string $table) : string;

    /**
     * This describes the table in the database using the new format.
     *
     * @internal this is the new function, will evenutally replace schema
     *
     * @param string $table
     * @return array
     */
    abstract public function describe(string $table) : array;

    /**
     * Generates the create table SQL, this is return as an array since some engines require
     * multiple statements. e.g. postgresql adds indexes and comments after the table has been
     * created.
     *
     * @param string $table
     * @param array $schema
     * @param array $options
     * @return array
     */
    abstract public function createTableSql(string $table, array $schema, array $options = []) : array;
   
    /**
     * Builds the create Table statements
     *
     * @param string $table
     * @param array $columns
     * @param array $constraints
     * @param array $indexes
     * @param array $options
     * @return array
     */
    abstract protected function buildCreateTableSql(string $table, array $columns, array $constraints, array $indexes, array $options = []) : array;

    abstract public function disableForeignKeySql() : string;
    
    abstract public function enableForeignKeySql() : string;

    /**
    * creates the table index
    *
    * @param array $attributes
    * @return string
    */
    abstract protected function tableIndex(array $attributes) : string;
    
    /**
    * Creates the contraint code
    *
    * @param array $attributes
    * @return string
    */
    abstract protected function tableConstraint(array $attributes) : string;

    /**
    * This creates a foreignKey table parameter
    *
    * @param array $attributes name,columns,references, update,delete
    * @return string
    */
    abstract protected function tableConstraintForeign(array $attributes) :string;

    /**
     * Maps the onclause value
     *
     * @param string $value
     * @return string
     */
    protected function onClause(string $value) : string
    {
        $map = ['cascade' => 'CASCADE','restrict' => 'RESTRICT','setNull' => 'SET NULL','setDefault' => 'SET DEFAULT','noAction' => 'NO ACTION'];

        return $map[$value] ?? 'RESTRICT';
    }
    /**
     * returns SQL statement for adding a column to an existing table
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
     * @return string
     */
    public function addColumn(string $table, string $name, string $type, array $options = []) : string
    {
        $definition = $this->columnSql(array_merge(['name' => $name,'type' => $type], $options));

        return sprintf('ALTER TABLE %s ADD COLUMN %s', $this->quoteIdentifier($table), $definition);
    }

    /**
     * returns SQL statement for removing an index on table
     *
     * @param string $table
     * @param string|array $column owner_id, [owner_id,tenant_id]
     * @param string $name name of index
     * @param array $options
     *  - unique: default:false set to to true to create unique index.
     *  - type: use this to set a specific type. e.g fulltext
     * @return string
     */
    public function addIndex(string $table, $column, string $name, array $options = []) : string
    {
        if (is_array($column)) {
            $column = implode(', ', $column);
        }
        $sql = 'CREATE INDEX %s ON %s (%s)';
        if (! empty($options['unique'])) {
            $sql = 'CREATE UNIQUE INDEX %s ON %s (%s)';
        }
        if (! empty($options['type'])) {
            $sql = 'CREATE ' . strtoupper($options['type']) . ' INDEX %s ON %s (%s)';
        }

        return sprintf($sql, $this->quoteIdentifier($name), $this->quoteIdentifier($table), $column);
        
        /*
         if (! empty($options['unique'])) {
             return "CREATE UNIQUE INDEX {$name} ON {$table} ({$column})";
         }
         if (! empty($options['type'])) {
             return 'CREATE ' . strtoupper($options['type']) . " INDEX {$name} ON {$table} ({$column})";
         }

         return "CREATE INDEX {$name} ON {$table} ({$column})";
         */
    }

    /**
     * Returns a string for removing an index on table
     *
     * @param string $table
     * @param string $name
     * @return string
     */
    abstract public function removeIndex(string $table, string $name) : string;

    /**
     * Renames an index
     * @requires MySQL 5.7+
     *
     * @param string $table
     * @param string $oldName
     * @param string $newName
     * @return string
     */
    abstract public function renameIndex(string $table, string $oldName, string $newName) : string;
 
    /**
    * Returns SQL for adding a foreignKey
    *
    * @param string $fromTable
    * @param string $name
    * @param string $column
    * @param string $toTable
    * @param string $primaryKey
    * @param string $onUpdate (cascade,restrict,setNull,setDefault,noAction)
    * @param string $onDelete (cascade,restrict,setNull,setDefault,noAction)
    * @return string
    */
    public function addForeignKey(string $fromTable, string $name, string $column, string $toTable, string $primaryKey, string $onUpdate = null, string $onDelete = null) : string
    {
        $fragment = $this->tableConstraintForeign([
            'name' => $name,
            'column' => $column,
            'references' => [$toTable,$primaryKey],
            'update' => $onUpdate,
            'delete' => $onDelete,
        ]);

        return sprintf('ALTER TABLE %s ADD ', $this->quoteIdentifier($fromTable)) . $fragment;
    }

    /**
     * Returns SQL for removing a foreignKey
     *
     * @param string $fromTable
     * @param string $constraint
     * @return string
     */
    abstract public function removeForeignKey(string $fromTable, string $constraint) : string;
   
    /**
     * Gets a list of foreignKeys
     *
     * @param string $table
     * @return array
     */
    abstract public function foreignKeys(string $table) : array;

    /**
     * Returns a list of indexes on a table
     *
     * @param string $table
     * @return array
     */
    abstract public function indexes(string $table) : array;

    /**
     * Checks if a foreignKey exists
     *
     * @param string $table
     * @param array $options uses keys column and name.
     * @return bool
     */
    public function foreignKeyExists(string $table, array $options) : bool
    {
        $options += ['column' => null,'name' => null];
        $foreignKeys = $this->foreignKeys($table);
      
        foreach ($foreignKeys as $fk) {
            if ($options['column'] and $fk['column'] == $options['column']) {
                return true;
            }
            if ($options['name'] and $fk['name'] == $options['name']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Changes a column according to the new definition
     *
     * @param string $table
     * @param string $name
     * @param array $options
     * @return string
     */
    abstract public function changeColumn(string $table, string $name, string $type, array $options = []) : string;

    /**
     * Returns a SQL statement for dropping a table
     *
     * @param string $table
     * @param array $options (ifExists)
     * @return string
     */
    abstract public function dropTableSql(string $table, array $options = []) : string;

    /**
     * Returns the sql for truncating the table
     *
     * @param string $table
     * @return string
     */
    abstract public function truncateTableSql(string $table) : string;

    /**
     * Sets the auto increment value for a auto increment/serial colun
     *
     * @param string $table
     * @param string $column
     * @param integer $counter
     * @return string
     */
    abstract public function changeAutoIncrementSql(string $table, string $column, int $counter) : string;
    /**
     * Gets a SQL statement for renaming a table
     *
     * @param string $from
     * @param string $to
     * @return string
     */
    abstract public function renameTable(string $from, string $to) : string;

    /**
     * Gets a SQL statement for renaming a table
     *
     * @param string $table
     * @param string $from
     * @param string $to
     * @return string
     */
    abstract public function renameColumn(string $table, string $from, string $to) : string;

    /**
     * Removes a column from the table§
     *
     * @param string $table
     * @param string $column
     * @return string
     */
    public function removeColumn(string $table, string $column) : string
    {
        return sprintf(
            'ALTER TABLE %s DROP COLUMN %s',
            $this->quoteIdentifier($table),
            $this->quoteIdentifier($column)
        );
        //return "ALTER TABLE {$table} DROP COLUMN {$column}";
    }

    /**
     * Removes multiple columns from the table
     *
     * @param string $table
     * @param array $columns
     * @return string
     */
    public function removeColumns(string $table, array $columns) : string
    {
        $out = [];
        foreach ($columns as $column) {
            $out[] = 'DROP COLUMN ' . $this->quoteIdentifier($column);  //$sql .= "\nDROP COLUMN {$column},";
        }

        return 'ALTER TABLE ' . $this->quoteIdentifier($table) . "\n" . implode(",\n", $out);
    }

    /**
     * Returns a list of tables
     *
     * @return array
     */
    public function tables() : array
    {
        return $this->connection()->tables();
    }

    /**
     * Checks if a table exists
     *
     * @param string $name
     * @return bool
     */
    public function tableExists(string $name) : bool
    {
        return in_array($name, $this->tables());
    }

    /**
     * Returns an array of columns
     *
     * @param string $table
     * @return array
     */
    public function columns(string $table) : array
    {
        $schema = $this->describe($table)['columns'];

        return array_keys($schema);
    }

    /**
     * Checks if a column exists in a table
     *
     * @param string $table
     * @param string $column
     * @return bool
     */
    public function columnExists(string $table, string $column) : bool
    {
        return in_array($column, $this->columns($table));
    }

    /**
     * Sets and gets the datasource
     *
     * @param string $datasource
     * @return string|string
     */
    public function datasource(string $datasource = null) : string
    {
        if ($datasource === null) {
            return $this->datasource;
        }

        return $this->datasource = $datasource;
    }

    /**
     * An internal helper function for fetch
     *
     * @param string $sql
     * @return array
     */
    protected function fetchRow(string $sql) : array
    {
        $connection = $this->connection();
        $connection->execute($sql);

        return $connection->fetch();
    }
    /**
     * An internal helper function for fetchAll
     *
     * @param string $sql
     * @return array
     */
    protected function fetchAll(string $sql) : array
    {
        $connection = $this->connection();
        $connection->execute($sql);
        $results = $connection->fetchAll();
        if ($results) {
            return $results;
        }

        return [];
    }

    /**
     * Returns the datasource
     *
     * @return \Origin\Model\Datasource
     */
    public function connection() : Datasource
    {
        return ConnectionManager::get($this->datasource);
    }

    /**
    * Wraps a column or table name in quotes
    *
    * @param string $value
    * @return string
    */
    public function quoteIdentifier(string $value) : string
    {
        return $this->quote . $value . $this->quote;
    }

    /**
     * Prepare value for schema
     *
     * @param mixed $value
     * @return mixed
     */
    abstract public function schemaValue($value);

    /**
    * Standardizes default values
    *
    * @param mixed $value
    * @return mixed
    */
    protected function defaultValue(string $type, $value)
    {
        if (in_array($type, ['bigint','integer','float','decimal'])) {
            if (is_numeric($value)) {
                return ($value == (int) $value) ? (int) $value : (float) $value;
            }
        } elseif ($type === 'boolean') {
            return (bool) $value;
        }

        return $value;
    }
}
