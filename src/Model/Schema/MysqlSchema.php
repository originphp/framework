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
 * There are suttle changes here, so this cannot be just droped in model driver. E.g. decimal and numeric does not have limit
 *
 */

namespace Origin\Model\Schema;

use Origin\Exception\Exception;
use Origin\Model\ConnectionManager;

class MysqlSchema extends BaseSchema
{
    /**
     * The quote character for table names and columns
     *
     * @var string
     */
    protected $quote = '`';

    /**
     * MySQL TinyText field length
     */
    const TINYTEXT = 255;

    /**
     * MySQL MediumText field length
     */
    const MEDIUMTEXT = 16777215;

    /**
     * MySQL LongText field length
     */
    const LONGTEXT = 4294967295;

    /**
     * MySQL column limits for texts
     *
     * @var array
     */
    protected $columnLimits = [
        'tinytext' => self::TINYTEXT,
        'longtext' => self::LONGTEXT,
        'mediumtext' => self::MEDIUMTEXT,
    ];
  
    /**
     * This describes the table in the database using the new format. This will require caching due to the amount
     * of queries that will need to be executed.
     *
     * @internal this is the new function, will evenutally replace schema
     *
     * @param string $table
     * @return array
     */
    public function describe(string $table) : array
    {
        $results = $this->fetchAll('SHOW FULL COLUMNS FROM ' . $this->quoteIdentifier($table));
        $columns = $this->convertTableDescription($results);

        $indexes = $constraints = [];
     
        /**
         * Convert constraints
         */
     
        foreach ($this->indexes($table) as $index) {
            $name = $index['name'];
            if ($index['name'] === 'PRIMARY') {
                $constraints['primary'] = ['type' => 'primary','column' => $index['column']];
            } elseif ($index['type'] === 'unique') {
                $constraints[$name] = ['type' => 'unique','column' => $index['column']];
            } else {
                $indexes[$name] = ['type' => 'index','column' => $index['column']];
            }
        }

        foreach ($this->foreignKeys($table) as $foreignKey) {
            $name = $foreignKey['name'];
            $constraints[$name] = [
                'type' => 'foreign',
                'column' => $foreignKey['column'],
                'references' => [$foreignKey['referencedTable'],$foreignKey['referencedColumn']],
            ];
        }

        $row = $this->fetchRow("SHOW TABLE STATUS WHERE Name =  '{$table}'");
        $options = [
            'engine' => $row['Engine'],
            'collation' => $row['Collation'],
        ];

        return ['columns' => $columns,'constraints' => $constraints,'indexes' => $indexes, 'options' => $options];
    }

    /**
     * Converts the results from the describeTableSQL to abstract
     *
     * @param array $data
     * @return array
     */
    protected function convertTableDescription(array $data) : array
    {
        $out = [];
        
        foreach ($data as $row) {
            $definition = $this->parseColumn($row['Type']);
            $definition += [
                'null' => $row['Null'] === 'YES'?true:false,
                'default' => $row['Default'],
            ];
            if (! empty($row['Collation'])) {
                $definition['collate'] = $row['Collation'];
            }
            if (! empty($row['Comment'])) {
                $definition['comment'] = $row['Comment'];
            }

            if (isset($row['Extra']) and $row['Extra'] === 'auto_increment') {
                $definition['autoIncrement'] = true;
            }
            $out[$row['Field']] = $definition;
        }

        return $out;
    }

    /**
     * Parses column data
     *
     * @see https://dev.mysql.com/doc/refman/8.0/en/data-types.html
     * @see https://dev.mysql.com/doc/refman/5.5/en/data-types.html
     *
     * @param string $column e.g int(11) unsigned
     * @return array
     */
    protected function parseColumn(string $column) : array
    {
        preg_match('/([a-z]+)(?:\(([0-9,]+)\))*/i', $column, $matches); //'int(10) unsigned'
        if (empty($matches)) {
            throw new Exception(sprintf('Error parsing %s', $column));
        }

        $unsigned = substr(strtolower($column), -8) === 'unsigned';
        $limit = $precision = $scale = null;

        $col = strtolower($matches[1]);
        if (isset($matches[2])) {
            $limit = (int) $matches[2]; // convert to int
        }

        if ($col === 'varchar') {
            return ['type' => 'string','limit' => $limit];
        }

        if ($col === 'int') {
            return ['type' => 'integer','limit' => $limit,'unsigned' => $unsigned];
        }
        /**
         * Handle floats and decimals. map doubles to decimal
         */
        if (in_array($col, ['float','decimal','double'])) {
            if (strpos($matches[2], ',') !== false) {
                list($precision, $scale) = explode(',', $matches[2]);
                $precision = (int) $precision; // important
                $scale = (int) $scale;
            }

            return ['type' => $col === 'float'?'float':'decimal','precision' => $precision, 'scale' => $scale,'unsigned' => $unsigned];
        }

        if (in_array($col, ['date','datetime','time','timestamp'])) {
            return ['type' => $col];
        }

        if ($col === 'text') {
            return ['type' => 'text'];
        }

        if ($col === 'tinyint' and $limit === 1) {
            return ['type' => 'boolean'];
        }

        if ($col === 'bigint') {
            return ['type' => 'bigint','limit' => $limit,'unsigned' => $unsigned];
        }

        if ($col === 'char') {
            return ['type' => 'string','limit' => $limit,'fixed' => true];
        }

        if (in_array($col, ['tinytext','longtext','mediumtext'])) {
            return ['type' => 'text', 'limit' => $this->columnLimits[$col]];
        }
       
        if ($col === 'blob') {
            return ['type' => 'binary'];
        }

        return ['type' => 'string','limit' => $limit];
    }

    /**
     * This is the new build column
     *
     * @param array $data
     * @return string
     */
    protected function columnSql(array $data) : string
    {
        $out = $this->quoteIdentifier($data['name']);

        $typeMap = [
            'string' => 'VARCHAR',
            'text' => 'TEXT',
            'integer' => 'INT',
            'bigint' => 'BIGINT',
            'float' => 'FLOAT',
            'decimal' => 'DECIMAL',
            'datetime' => 'DATETIME',
            'date' => 'DATE',
            'time' => 'TIME',
            'timestamp' => 'TIMESTAMP',
            'binary' => 'BLOB',
            'boolean' => 'TINYINT',
        ];

        /**
         * Work with mapped or custom types
         */
        $type = $data['type'];
        $isMapped = isset($typeMap[$data['type']]);
        if ($isMapped) {
            $type = $typeMap[$data['type']];
        }
        
        /**
         * Handle specials and default values
         */
        if ($data['type'] === 'string') {
            if (! isset($data['limit'])) {
                $data['limit'] = 255;
            }
            if (! empty($data['fixed'])) {
                $type = 'CHAR';
            }
        } elseif ($data['type'] === 'text') {
            if (! empty($data['limit']) and in_array($data['limit'], $this->columnLimits)) {
                $key = array_search($data['limit'], $this->columnLimits);
                $type = strtoupper($key);
            }
        } elseif ($data['type'] === 'boolean') {
            $type = 'TINYINT(1)';
        }

        $out .= ' ' . $type;

        // deal with types that have limits or custom types with limit set
        if (! empty($data['limit']) and (in_array($data['type'], ['integer','string','bigint']) or ! $isMapped)) {
            $out .= '(' . $data['limit'] . ')';
        } elseif (! empty($data['precision']) and (in_array($data['type'], ['decimal','float']) or ! $isMapped)) {
            $out .= '(' . $data['precision'] .',' . ($data['scale'] ?? 0) . ')'; // 0 is MySQL default
        }

        // deal with unsigned
        if (in_array($data['type'], ['integer','bigint','decimal','float']) and ! empty($data['unsigned'])) {
            $out .= ' UNSIGNED';
        }

        if (in_array($data['type'], ['string','text']) and ! empty($data['collate'])) {
            $out .= ' COLLATE ' . $data['collate'];
        }
        
        if (isset($data['null']) and $data['null'] === false) {
            $out .= ' NOT NULL';
        }

        if ($data['type'] === 'timestamp') {
            if (isset($data['null']) and $data['null'] === true) {
                $out .= ' NULL';
            }
            if (isset($data['default']) and strtolower($data['default']) === 'current_timestamp') {
                $out .= ' DEFAULT CURRENT_TIMESTAMP';
                unset($data['default'],$data['null']);
            }
        }

        if (in_array($data['type'], ['integer','bigint']) and ! empty($data['autoIncrement'])) {
            $out .= ' AUTO_INCREMENT';
        } elseif (isset($data['default'])) {
            $out .= ' DEFAULT ' . $this->schemaValue($data['default']);
        }
        
        if (! empty($data['comment'])) {
            $out .= ' COMMENT ' . $this->schemaValue($data['comment']);
        }
        
        return $out;
    }

    /**
     * Returns indexes (indicies)
     *
     * @param string $table
     * @return array
     */
    public function indexes(string $table) : array
    {
        $sql = sprintf('SHOW INDEX FROM %s', $this->quoteIdentifier($table));
        $results = $this->fetchAll($sql);
        $indexes = [];
      
        foreach ($results as $result) {
            /**
             * Handle multiple columns in a index
             */
            if ($result['Seq_in_index'] > 1) {
                $key = count($indexes) - 1;
                $indexes[$key]['column'] = (array) $indexes[$key]['column'];
                $indexes[$key]['column'][] = $result['Column_name'];
                continue;
            }

            $indexes[] = [
                'name' => $result['Key_name'],
                'column' => $result['Column_name'],
                'type' => ($result['Non_unique'] == 0) ?'unique' : 'index',
            ];

            /**
             * Full text support
             */
            if ($result['Index_type'] === 'FULLTEXT') {
                $indexes[count($indexes) - 1]['type'] = 'fulltext';
            }
        }

        return  $indexes;
    }

    /**
     * Returns a rename table SQL statement
     *
     * @param string $from
     * @param string $to
     * @return string
     */
    public function renameTable(string $from, string $to) : string
    {
        return sprintf(
            'RENAME TABLE %s TO %s',
            $this->quoteIdentifier($from),
            $this->quoteIdentifier($to)
            );
        
        //return  "RENAME TABLE {$from} TO {$to}";
    }

    /**
     * Changes a column according to the new definition
     *
     * @param string $table
     * @param string $name
     * @param array $options
     * @return string
     */
    public function changeColumn(string $table, string $name, string $type, array $options = []) : string
    {
        $options += ['name' => $name, 'type' => $type];

        return sprintf(
            'ALTER TABLE %s MODIFY COLUMN %s',
            $this->quoteIdentifier($table),
            $this->columnSql($options)
        );
        //return "ALTER TABLE {$table} MODIFY COLUMN {$definition}";
    }

    /**
     * Returns a rename column SQL statement
     * @internal Changed to be compatable with older versions of MySQL e.g 5.x
     * @param string $table
     * @param string $from
     * @param string $to
     * @return string
     */
    public function renameColumn(string $table, string $from, string $to) : string
    {
        $tableSchema = $this->describe($table)['columns'];
        $definition = '';

        $schema = null;

        /**
         * Make it reversable
         * @this should not be here
         */
        if (isset($tableSchema[$from])) {
            $schema = $tableSchema[$from];
        } elseif (isset($tableSchema[$to])) {
            $schema = $tableSchema[$to];
        }

        if ($schema) {
            $data = $this->columns[$schema['type']];
            $definition = strtoupper($data['name']);
            if (strpos($definition, ' ') !== false) {
                list($definition, $void) = explode(' ', $definition);
            }
            if (! empty($schema['limit'])) {
                $definition .= "({$schema['limit']})";
            } elseif (! empty($data['precision'])) {
                $definition .= "({$schema['precision']},{$schema['scale']})";
            }
        }

        return sprintf(
            'ALTER TABLE %s CHANGE %s %s %s',
            $this->quoteIdentifier($table),
            $this->quoteIdentifier($from),
            $this->quoteIdentifier($to),
            $definition
        );
        //return "ALTER TABLE {$table} CHANGE {$from} {$to} {$definition}";
    }

    /**
     * Returns a remove index SQL statement
     * @see https://dev.mysql.com/doc/refman/8.0/en/drop-index.html
     * @param string $table
     * @param string|array $column owner_id, [owner_id,tenant_id]
     * @param array $options
     *  - name: name of index
     * @return string
     */
    public function removeIndex(string $table, string $name) : string
    {
        return sprintf(
            'DROP INDEX %s ON %s',
            $this->quoteIdentifier($name),
            $this->quoteIdentifier($table)
        );
        //return "DROP INDEX {$name} ON {$table}";
    }

    /**
     * Renames an index
     * @requires MySQL 5.7+
     *
     * @param string $table
     * @param string $oldName
     * @param string $newName
     * @return string
     */
    public function renameIndex(string $table, string $oldName, string $newName) : string
    {
        return sprintf(
            'ALTER TABLE %s RENAME INDEX %s TO %s',
            $this->quoteIdentifier($table),
            $this->quoteIdentifier($oldName),
            $this->quoteIdentifier($newName)
        );
        //return "ALTER TABLE {$table} RENAME INDEX {$oldName} TO {$newName}";
    }

    /**
     * Returns a list of foreign keys on a table
     *
     * @param string $table
     * @return array
     */
    public function foreignKeys(string $table) : array
    {
        $config = ConnectionManager::config($this->datasource);

        /*$sql = "SELECT TABLE_NAME,COLUMN_NAME,CONSTRAINT_NAME, REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_SCHEMA = '{$config['database']}' AND TABLE_NAME = '{$table}';";*/
        $sql = sprintf(
            'SELECT TABLE_NAME,COLUMN_NAME,CONSTRAINT_NAME, REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_SCHEMA = %s AND TABLE_NAME = %s',
            $this->schemaValue($config['database']),
            $this->schemaValue($table)
        );

        $out = [];
        foreach ($this->fetchAll($sql) as $result) {
            $out[] = [
                'name' => $result['CONSTRAINT_NAME'],
                'table' => $result['TABLE_NAME'],
                'column' => $result['COLUMN_NAME'],
                'referencedTable' => $result['REFERENCED_TABLE_NAME'],
                'referencedColumn' => $result['REFERENCED_COLUMN_NAME'],
            ];
        }

        return $out;
    }
    /**
     * Returns a remove foreignKey constraint SQL statement
     *
     * @param string $fromTable
     * @param [type] $constraint
     * @return string
     */
    public function removeForeignKey(string $fromTable, string $constraint) : string
    {
        return sprintf(
            'ALTER TABLE %s DROP FOREIGN KEY %s',
            $this->quoteIdentifier($fromTable),
            $this->quoteIdentifier($constraint)
    );
        //return "ALTER TABLE {$fromTable} DROP FOREIGN KEY {$constraint}";
    }
    /**
     * Undocumented function
     *
     * @param string $table
     * @return strings
     */
    public function showCreateTable(string $table) : string
    {
        $result = $this->fetchRow('SHOW CREATE TABLE ' . $this->quoteIdentifier($table));

        return $result['Create Table'];
    }

    /**
    * This is the new create Table function
    *
    * @param string $table
    * @param array $schema
    * @param array $options
    * @return array
    */
    public function createTableSql(string $table, array $schema, array $options = []) : array
    {
        $columns = $constraints = $indexes = $databaseOptions = [];
        
        # All Databases
        foreach ($schema as $name => $definition) {
            if (is_string($definition)) {
                $definition = ['type' => $definition];
            }
            $columns[] = '  ' . $this->columnSql(['name' => $name] + $definition);
        }
        
        if (isset($options['constraints'])) {
            foreach ($options['constraints'] as $name => $definition) {
                $constraints[] = '  ' .  $this->tableConstraint(['name' => $name] + $definition);
            }
        }
        
        if (isset($options['indexes'])) {
            foreach ($options['indexes'] as $name => $definition) {
                $indexes[] = '  ' .  $this->tableIndex(['name' => $name] + $definition);
            }
        }

        # MySQL Options
        if (isset($options['options'])) {
            $databaseOptions = $options['options'];
            /**
             * @deprecated This provides backwards comptability
             */
            if (is_string($databaseOptions)) {
                $databaseOptions = ['options' => $options];
            }
        }

        return $this->buildCreateTableSql($table, $columns, $constraints, $indexes, $databaseOptions);
    }

    /**
     * Builds the create Table SQL
     *
     * @param string $table
     * @param array $columns
     * @param array $constraints
     * @param array $indexes
     * @param array $options
     * @return array
     */
    protected function buildCreateTableSql(string $table, array $columns, array $constraints, array $indexes, array $options = []) : array
    {
        $out = sprintf(
            "CREATE TABLE %s (\n%s\n)",
            $this->quoteIdentifier($table),
            implode(",\n", array_merge($columns, $constraints, $indexes))
        );

        /**
         * Options string support. This is used in Migrations
         */
        if (isset($options['options']) and is_string($options['options'])) {
            deprecationWarning('Creating tables with option strings are depreciated use array instead');

            return $out . ' ' . $options['options'];
        }

        if (isset($options['engine'])) {
            $out .= ' ENGINE=' . $options['engine'];
        }

        /**
         * @see https://dev.mysql.com/doc/refman/8.0/en/charset-table.html
         */
        if (isset($options['charset'])) {
            $out .= ' DEFAULT CHARACTER SET=' . $options['charset'];
        }
        
        if (isset($options['collate'])) {
            $out .= ' COLLATE=' . $options['collate'];
        }
      
        return [$out];
    }

    /**
        * Creates the contraint code
        *
        * @param string $table
        * @param array $attributes
        * @return string
        */
    protected function tableConstraint(array $attributes) : string
    {
        $columns = implode(',', (array) $attributes['column']);
        if ($attributes['type'] === 'primary') {
            return sprintf('PRIMARY KEY (%s)', $columns);
        }
        // unique constraint and index very similar, here using key terminology
        if ($attributes['type'] === 'unique') {
            return sprintf('UNIQUE KEY (%s)', $columns);
        }
       
        if ($attributes['type'] === 'foreign') {
            return $this->tableConstraintForeign($attributes);
        }
        throw new Exception(sprintf('Unknown constriant %s', $attributes['type']));
    }

    /**
    * creates the table index
    *
    * @param array $attributes
    * @return string
    */
    protected function tableIndex(array $attributes) : string
    {
        $string = null;

        if ($attributes['type'] === 'index') {
            $string = 'INDEX %s (%s)';
        } elseif ($attributes['type'] === 'unique') {
            $string = 'UNIQUE INDEX %s (%s)';
        } elseif ($attributes['type'] === 'fulltext') {
            $string = 'FULLTEXT INDEX %s (%s)';
        }
        if ($string) {
            $columns = implode(',', (array) $attributes['column']);

            return sprintf($string, $this->quoteIdentifier($attributes['name']), $columns);
        }
   
        throw new Exception(sprintf('Unknown index type %s', $attributes['type']));
    }

    /**
     * Prepares a column value
     *
     * @param mixed $value
     * @return mixed
     */
    public function schemaValue($value)
    {
        if ($value === '' or $value === null) {
            return 'NULL';
        }
        if (is_bool($value)) {
            if ($value === true) {
                return 1;
            }

            return 0;
        }
        if (is_numeric($value)) {
            return $value;
        }

        return "'{$value}'";
    }

    /**
    * Returns a SQL statement for dropping a table
    *
    * @param string $table
    * @param array options (ifExists)
    * @return string
    */
    public function dropTable(string $table, array $options = []) : string
    {
        $sql = 'DROP TABLE %s';
        if (! empty($options['ifExists'])) {
            $sql = 'DROP TABLE IF EXISTS %s';
        }

        return sprintf($sql, $this->quoteIdentifier($table));
    }

    # # # CODE HERE WILL BE DEPRECATED IN FUTURE # # #

    /**
       * This is the map for database agnostic, if its not found here then
       * use what user supplies.
       * @internal this is being deprecated in future
       * @var array
       */
    protected $columns = [
        'primaryKey' => ['name' => 'INT NOT NULL AUTO_INCREMENT'],
        'string' => ['name' => 'VARCHAR', 'limit' => 255],
        'text' => ['name' => 'TEXT'],
        'integer' => ['name' => 'INT', 'limit' => 11],
        'bigint' => ['name' => 'BIGINT', 'limit' => 20], // chgabged
        'float' => ['name' => 'FLOAT', 'precision' => 10, 'scale' => 0], // mysql defaults
        'decimal' => ['name' => 'DECIMAL', 'precision' => 10, 'scale' => 0],
        'datetime' => ['name' => 'DATETIME'],
        'time' => ['name' => 'TIME'],
        'timestamp' => ['name' => 'TIMESTAMP'],
        'date' => ['name' => 'DATE'],
        'binary' => ['name' => 'BLOB'],
        'boolean' => ['name' => 'TINYINT', 'limit' => 1],
    ];

    /**
    * Returns the schema for the table
    *
    * @param string $table
    * @return array
    */
    public function schema(string $table) : array
    {
        $schema = [];
        $results = $this->fetchAll("SHOW FULL COLUMNS FROM {$table}");

        $reverseMapping = [];
        foreach ($this->columns as $key => $value) {
            $reverseMapping[strtolower($value['name'])] = $key;
        }
        /**
         * @todo refactor to work similar to Postgres,not using reverse mapping. For cleanner
         * code. These are temporary solutions.
         * @see MySQL driver - duplicated there.
         */
        $reverseMapping['char'] = $reverseMapping['varchar']; // add missing type
        $reverseMapping['mediumtext'] = $reverseMapping['text']; // add missing type
        $reverseMapping['longtext'] = $reverseMapping['text']; // add missing type
      
        foreach ($results as $column) {
            $decimals = $length = null;
            $type = str_replace(')', '', $column['Type']);
            if (strpos($type, '(') !== false) {
                list($type, $length) = explode('(', $type);
                if (strpos($length, ',') !== false) {
                    list($length, $decimals) = explode(',', $length);
                }
            }

            if (isset($reverseMapping[$type])) {
                $type = $reverseMapping[$type];
                $schema[$column['Field']] = [
                    'type' => $type,
                    'limit' => ($length and ! in_array($type, ['boolean', 'decimal', 'numeric'])) ? (int) $length : null,
                    'default' => $column['Default'],
                    'null' => ($column['Null'] === 'YES' ? true : false),
                ];

                if (in_array($type, ['float', 'decimal'])) {
                    $schema[$column['Field']]['precision'] = $length;
                    $schema[$column['Field']]['scale'] = (int) $decimals;
                }
                if ($schema[$column['Field']]['limit'] === null) {
                    unset($schema[$column['Field']]['limit']);
                }
                if (in_array($type, ['timestamp', 'datetime'])) {
                    $schema[$column['Field']]['default'] = null; // remove current_timestamp
                }

                if ($column['Extra'] === 'auto_increment') {
                    $schema[$column['Field']]['autoIncrement'] = true;
                }

                if ($column['Key'] === 'PRI' and $column['Extra'] === 'auto_increment') {
                    $schema[$column['Field']]['type'] = 'primaryKey';
                }
                /**
                 * @todo in postgresql cant get this work yet.
                 */
                if ($column['Key'] === 'PRI') {
                    $schema[$column['Field']]['key'] = 'primary';
                }
            }
        }

        return $schema;
    }
}
