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

use Origin\Model\ConnectionManager;
use Origin\Exception\Exception;

class BaseSchema
{
    public $datasource = null;

    protected $columns = [];

    public function __construct(string $datasource='default')
    {
        $this->datasource = $datasource;
    }

    /**
     * Build a native sql column string
     *
     * @param array $column
     *  - name: name of column
     *  - type: integer,bigint,float,decimail,datetime,date,binary or boolean
     *  - limit: length of field
     *  - precision: for decimals and floasts
     *  - default: default value use '' or nill for null value
     *  - null: allow null values
     * @return void
     */
    public function buildColumn(array $column)
    {
        $column += [
          'name' => null,
          'type' => null,
          'limit'=> null, // Max column limit [text, binary, integer]
          'precision'=> null, // decima, float
          'null' => null,
      ];
  
        if (empty($column['name'])) {
            throw new Exception('Column name not specificied');
        }
        if (empty($column['type'])) {
            throw new Exception('Column type not specificied');
        }
        $real = [];
        $type = $column['type'];
        if (isset($this->columns[$type])) {
            $real = $this->columns[$type];
            $type = strtoupper($this->columns[$type]['name']);
            # Remove limit,precision, scale if user has sent them (postgre can use int limit)
            foreach (['limit','precision','scale'] as $remove) {
                if (!isset($real[$remove]) and isset($column[$remove])) {
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
            if (!empty($real['limit']) and empty($column['limit'])) {
                $column['limit'] = $real['limit'];
            }
            if (isset($real['precision']) and !isset($column['precision'])) {
                $column['precision'] = $real['precision'];
            }
            if (isset($real['scale']) and !isset($column['scale'])) {
                $column['scale'] = $real['scale'];
            }
        }
  
        if ($column['limit']) {
            $output .= "({$column['limit']})";
        } elseif (!empty($column['precision'])) {
            $output .= "({$column['precision']},{$column['scale']})";
        }

        if (isset($column['default']) and $column['default'] === '') {
            $column['default'] = null;
        }
       
        /**
         * First handle defaults, then nulls
         */
        if (isset($column['default']) and $column['null'] === false) {
            $output .= ' DEFAULT ' . $this->columnValue($column['default']) .' NOT NULL';
        } elseif (isset($column['default'])) {
            $output .= ' DEFAULT ' . $this->columnValue($column['default']);
        } elseif ($column['null']) {
            $output .= ' DEFAULT NULL';
        } elseif ($column['null'] === false) {
            $output .= ' NOT NULL';
        }
      
        return $output;
    }

    /**
    * Returns a MySQL string for creating a table.  Should be agnostic and non-agnostic.
    *
    * @param string $table
    * @param array $data
    * @return string
    */
    public function createTable(string $table, array $data, array $options=[]) : string
    {
        $append = '';
        if (!empty($options['options'])) {
            $append = ' '. $options['options'];
        }

        $result = [];
     
        $primaryKeys = [];
        foreach ($data as $field => $settings) {
            if (!empty($settings['key'])) {
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
                if (!in_array($field, $primaryKeys)) {
                    $primaryKeys[] = $field;
                }
                $result[] = ' ' . $field . ' ' . $this->columns['primaryKey']['name'];
                continue;
            }

            $mapping = ['name'=>$settings['type']]; // non agnostic
            if (isset($this->columns[$settings['type']])) {
                $mapping = $this->columns[$settings['type']];
                // Remove these fields if they are not allowed per columns
                foreach (['limit','precision','scale'] as $remove) {
                    if (!isset($mapping[$remove])) {
                        unset($settings[$remove]);
                    }
                }
            }

            $settings = $settings + $mapping;

            $output = $field . ' ' .  strtoupper($mapping['name']);
         
            if (!empty($settings['limit'])) {
                $output .= "({$settings['limit']})";
            } elseif (in_array($settings['type'], ['decimal', 'float'])) {
                if (isset($settings['scale'])) {
                    $output .= "({$settings['precision']},{$settings['scale']})";
                } elseif (isset($settings['precision'])) {
                    $output .= "({$settings['precision']})"; // postgres float
                }
            }

            if (isset($settings['default'])) {
                $output .= " DEFAULT '{$settings['default']}'";
            }

            if (isset($settings['null'])) {
                if ($settings['null'] == true) {
                    $output .= ' NULL';
                } else {
                    $output .= ' NOT NULL';
                }
            }
            $result[] = ' '.$output;
        }
      
        if ($primaryKeys) {
            $result[] = ' PRIMARY KEY ('.implode(',', $primaryKeys).')';
        }
        return "CREATE TABLE {$table} (\n".implode(",\n", $result)."\n){$append}";
    }

    /**
     * Adds a column to an existing table
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
     */
    public function addColumn(string $table, string $name, string $type, array $options=[])
    {
        $definition = $this->buildColumn(array_merge(['name'=>$name,'type'=>$type], $options));
        return "ALTER TABLE {$table} ADD COLUMN {$definition}";
    }

    /**
     * Removes an index on table
     *
     * @param string $table
     * @param string|array $column owner_id, [owner_id,tenant_id]
     * @param array $options
     *  - name: name of index
     * @return string
     */
    public function addIndex(string $table, string $column, string $name, array $options=[]) : string
    {
        if (!empty($options['unique'])) {
            return "CREATE UNIQUE INDEX {$name} ON {$table} ({$column})";
        }
        return "CREATE INDEX {$name} ON {$table} ({$column})";
    }

    /**
     * Removes an index on table
     *
     * @param string $table
     * @param string|array $column owner_id, [owner_id,tenant_id]
     * @param array $options
     *  - name: name of index
     * @return string
     */
    public function removeIndex(string $table, string $name)
    {
    }

    /**
     * Renames an index
     * @requires MySQL 5.7+
     *
     * @param string $table
     * @param string $oldName
     * @param string $newName
     * @return void
     */
    public function renameIndex(string $table, string $oldName, string $newName)
    {
    }
 
    public function addForeignKey(string $fromTable, string $toTable, array $options=[])
    {
        return "ALTER TABLE {$fromTable} ADD CONSTRAINT {$options['name']} FOREIGN KEY ({$options['column']}) REFERENCES {$toTable} ({$options['primaryKey']})";
    }

    public function removeForeignKey(string $fromTable, $constraint)
    {
        return "ALTER TABLE {$fromTable} DROP FOREIGN KEY {$constraint}";
    }

    /**
     * Gets a list of foreignKeys
     *
     * @param string $table
     * @return void
     */
    public function foreignKeys(string $table)
    {
    }

    public function indexes(string $table)
    {
    }

    /**
     * Checks if a foreignKey exists
     *
     * @param string $table
     * @param string $foreignKey
     * @return bool
     */
    public function foreignKeyExists(string $table, array $options)
    {
        $options += ['column'=>null,'name'=>null];
        $foreignKeys = $this->foreignKeys($table);
      
        foreach ($foreignKeys as $fk) {
            if ($options['column'] and $fk['column_name'] == $options['column']) {
                return true;
            }
            if ($options['name'] and $fk['constraint_name'] == $options['name']) {
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
    public function changeColumn(string $table, string $name, string $type, array $options=[])
    {
    }

    /**
       * Drops a table from database
       *
       * @param string $table
       * @return string
       */
    public function dropTable(string $table)
    {
        return "DROP TABLE {$table}";
    }

    /**
     * Renames a table
     *
     * @param string $from
     * @param string $to
     * @return string
     */
    public function renameTable(string $from, string $to)
    {
    }

    /**
     * Renames a column name
     *
     * @param string $table
     * @param string $from
     * @param string $to
     * @return string
     */
    public function renameColumn(string $table, string $from, string $to)
    {
        return  "ALTER TABLE {$table} RENAME COLUMN {$from} TO {$to}";
    }

    /**
     * Removes a column from the table§
     *
     * @param string $table
     * @param string $column
     * @return string
     */
    public function removeColumn(string $table, string $column)
    {
        return "ALTER TABLE {$table} DROP COLUMN {$column}";
    }

    /**
     * Removes multiple columns from the table
     *
     * @param string $table
     * @param array $columns
     * @return string
     */
    public function removeColumns(string $table, array $columns)
    {
        $sql = "ALTER TABLE {$table}";
        foreach ($columns as $column) {
            $sql .="\nDROP COLUMN {$column},";
        }
        return substr($sql, 0, -1);
    }

    /**
     * Returns an list of tables
     *
     * @return array
     */
    public function tables()
    {
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
     * Undocumented function
     *
     * @param string $table
     * @return string
     */
    public function showCreateTable(string $table)
    {
    }

    /**
     * Returns an array of columns
     *
     * @param string $table
     * @return array
     */
    public function columns(string $table)
    {
        $schema = $this->schema($table);
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
     * Return column name
     *
     * @param string $name
     * @return void
     */
    public function columnName(string $name)
    {
        return $name;
    }

    public function columnValue($value)
    {
        if ($value === null) {
            $value = 'NULL';
        }
        if (is_int($value)) {
            return $value;
        }
        return "'{$value}'";
    }

    public function schema(string $table)
    {
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
        * Fetchs a single row from the database
        *
        * @param string $sql
        * @return array|null
        */
    public function fetchRow(string $sql)
    {
        $connection = $this->connection();
        $connection->execute($sql);
        return $connection->fetch();
    }

    /**
    * Fetchs all rows from database
    *
    * @param string $sql
    * @return array
    */
    public function fetchAll(string $sql)
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
     * Returns the ConnectionManager
     *
     * @return \Origin\Model\ConnectionManager
     */
    public function connection()
    {
        return ConnectionManager::get($this->datasource);
    }
}
