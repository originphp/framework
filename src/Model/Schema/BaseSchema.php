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
  * Know Issues:
  * ============
  * - Cant set limit for primaryKey
  * - Cant detect primaryKey properly in postgresql without second query
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
    public $datasource = null;

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
     * @param array $data
     * @return void
     */
    abstract public function showCreateTable(string $table);

    /**
     * This describes the table in the database using the new format.
     *
     * @internal this is the new function, will evenutally replace schema
     *
     * @param string $table
     * @return array
     */
    abstract public function describe(string $table);

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
    abstract public function createTableSql(string $table, array $schema, array $options = []);
   
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

    /**
    * creates the table index
    *
    * @param array $attributes
    * @return string
    */
    abstract protected function tableIndex(array $attributes);
    
    /**
    * Creates the contraint code
    *
    * @param string $table
    * @param array $attributes
    * @return string
    */
    abstract protected function tableConstraint(array $attributes);

    /**
    * This creates a foreignKey table parameter
    *
    * @param array attributes name,columns,references, update,delete
    * @return string
    */
    protected function tableConstraintForeign(array $attributes) :string
    {
        $map = ['cascade' => 'CASCADE','restrict' => 'RESTRICT','setNull' => 'SET NULL','setDefault' => 'SET DEFAULT','noAction' => 'NO ACTION'];

        $sql = sprintf(
            'CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s (%s)',
            $attributes['name'],
            implode(', ', (array) $attributes['column']),
            $this->quoteIdentifier($attributes['references'][0]),
            $attributes['references'][1]
        );
        
        if (! empty($attributes['update']) or ! empty($attributes['delete'])) {
            $sql .= ' ' . sprintf('ON UPDATE %s ON DELETE %s', $map[$attributes['update']], $map[$attributes['update']]);
        }

        return $sql;
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
     * @param string|array $column owner_id, [owner_id,tenant_id]
     * @param array $options
     *  - name: name of index
     * @return string
     */
    abstract public function removeIndex(string $table, string $name);

    /**
     * Renames an index
     * @requires MySQL 5.7+
     *
     * @param string $table
     * @param string $oldName
     * @param string $newName
     * @return void
     */
    abstract public function renameIndex(string $table, string $oldName, string $newName);
 
    /**
     * Returns SQL for adding a foreignKey
     *
     * @param string $fromTable
     * @param string $name
     * @param string $column
     * @param string $toTable
     * @param string $primaryKey
     * @return string
     */
    public function addForeignKey(string $fromTable, string $name, string $column, string $toTable, string $primaryKey) : string
    {
        return sprintf(
            'ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s (%s)',
            $this->quoteIdentifier($fromTable),
            $this->quoteIdentifier($name),
            $column,
            $this->quoteIdentifier($toTable),
            $primaryKey
    );
    }

    /**
     * Returns SQL for removing a foreignKey
     *
     * @param string $fromTable
     * @param string $toTable
     * @param array $options
     * @return string
     */
    abstract public function removeForeignKey(string $fromTable, string $constraint);
   
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
     * @param string $foreignKey
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
    abstract public function changeColumn(string $table, string $name, string $type, array $options = []);

    /**
     * Returns a SQL statement for dropping a table
     *
     * @param string $table
     * @param array options (ifExists)
     * @return string
     */
    abstract public function dropTable(string $table, array $options = []);

    /**
     * Gets a SQL statement for renaming a table
     *
     * @param string $from
     * @param string $to
     * @return string
     */
    abstract public function renameTable(string $from, string $to);

    /**
     * Gets a SQL statement for renaming a table
     *
     * @param string $table
     * @param string $from
     * @param string $to
     * @return string
     */
    abstract public function renameColumn(string $table, string $from, string $to);

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
    public function tableExists(string $name)
    {
        return in_array($name, $this->tables());
    }

    /**
     * Returns an array of columns
     *
     * @param string $table
     * @return array
     */
    public function columns(string $table)
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
    public function columnExists(string $table, string $column)
    {
        return in_array($column, $this->columns($table));
    }

    /**
     * Sets and gets the datasource
     *
     * @param string $datasource
     * @return string|null
     */
    public function datasource(string $datasource = null)
    {
        if ($datasource === null) {
            return $this->datasource;
        }
        $this->datasource = $datasource;
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

    # # # CODE HERE WILL BE DEPRECATED IN FUTURE # # #

    /**
        * Returns a MySQL string for creating a table. Should be agnostic and non-agnostic.
        * @todo when this is removed, then createTableSQL might need to be renamed to be consistent.
        * @param string $table
        * @param array $data
        * @return string
        */
    public function createTable(string $table, array $data, array $options = []) : string
    {
        $options += ['primaryKey' => null,'options' => null];
        $append = '';
        if (! empty($options['options'])) {
            $append = ' '. $options['options'];
        }

        $result = [];
        
        /**
         * Create table accepts primaryKey settings
         * v1.25 +
         */
        $primaryKeys = [];
        if ($options['primaryKey']) {
            $primaryKeys = (array) $options['primaryKey'];
        }

        /**
         * This is legacy handler. for key setting
         */
        foreach ($data as $field => $settings) {
            if (! empty($settings['key']) and ! in_array($field, $primaryKeys)) {
                $primaryKeys[] = $field;
            }
        }
       
        foreach ($data as $field => $settings) {
            if (is_string($settings)) {
                $settings = ['type' => $settings];
            }
            /**
             * Cant set length on primaryKey
             */
            if ($settings['type'] === 'primaryKey') {
                if (! in_array($field, $primaryKeys)) {
                    $primaryKeys[] = $field;
                }
                $result[] = ' ' . $field . ' ' . $this->columns['primaryKey']['name'];
                continue;
            }

            $output = $this->buildColumn(['name' => $field] + $settings);
         
            $result[] = ' '.$output;
        }
      
        if ($primaryKeys) {
            $result[] = ' PRIMARY KEY ('.implode(',', $primaryKeys).')';
        }

        return "CREATE TABLE {$table} (\n".implode(",\n", $result)."\n){$append}";
    }

    /**
     * Prepares a column value
     *
     * @param mixed $value
     * @return mixed
     */
    public function columnValue($value)
    {
        return $this->schemaValue($value);
    }

    /**
        * Gets the schema for a table
        *
        * @param string $table
        * @return array
        */
    abstract public function schema(string $table);

    /**
     * Build a native sql column string
     *
     * @param array $column
     *  - name: name of column
     *  - type: integer,bigint,float,decimail,datetime,date,binary or boolean
     *  - limit: length of field
     *  - precision: for decimals and floasts
     *  - default: default value use ''
     *  - null: allow null values
     * @return string
     */
    protected function buildColumn(array $column) : string
    {
        $column += [
            'name' => null,
            'type' => null,
            'limit' => null, // Max column limit [text, binary, integer]
            'precision' => null, // decima, float
            'null' => null,
        ];

        $real = [];
        $type = $column['type'];

        /**
         * Temp solution until refactored
         */
        if (! empty($column['autoIncrement'])) {
            $type = 'primaryKey'; // this will be redonne to
        }
        if (isset($this->columns[$type])) {
            $real = $this->columns[$type];
            $type = strtoupper($this->columns[$type]['name']); // tmp

            /**
                 * Convert Limits for MySQL
                 * @todo how this be implemented in MySQL schema without duplicating
                 * code
                 */
            if ($column['type'] === 'text' and isset($column['limit'])) {
                $limit = $column['limit'];
                $type = 'TEXT';
                if ($limit === 16777215) {
                    $type = 'MEDIUMTEXT';
                } elseif ($limit === 4294967295) {
                    $type = 'LONGTEXT';
                }
            }

            //list($namespace, $class) = namespaceSplit(get_class($this));
            # Remove limit,precision, scale if user has sent them (postgre can use int limit)
            foreach (['limit','precision','scale'] as $remove) {
                if (! isset($real[$remove]) and isset($column[$remove])) {
                    $column[$remove] = null;
                }
            }
        }
     
        # Lengths
        $output = $this->columnName($column['name']) . ' ' . $type;
     
        /**
         * Logic when using agnostic
         * Get defaults if needed
         */
        if ($real) {
            if (! empty($real['limit']) and empty($column['limit'])) {
                $column['limit'] = $real['limit'];
            }
            if (isset($real['precision']) and ! isset($column['precision'])) {
                $column['precision'] = $real['precision'];
            }
            if (isset($real['scale']) and ! isset($column['scale'])) {
                $column['scale'] = $real['scale'];
            }
        }
  
        if ($column['limit']) {
            $output .= "({$column['limit']})";
        } elseif (! empty($column['precision'])) {
            $output .= "({$column['precision']},{$column['scale']})";
        }

        /**
         * First handle defaults, then nulls
         */
        if (! empty($column['default']) and $column['null'] === false) {
            $output .= ' DEFAULT ' . $this->schemaValue($column['default']) .' NOT NULL';
        } elseif (isset($column['default'])) { //isset catches ''
            $output .= ' DEFAULT ' . $this->schemaValue($column['default']);
        } elseif ($column['null'] === false) {
            $output .= ' NOT NULL';
        }
      
        return $output;
    }

    /**
     * Formats the column name
     *
     * @param string $name
     * @return string
     */
    public function columnName(string $name) : string
    {
        return $name;
    }
}
