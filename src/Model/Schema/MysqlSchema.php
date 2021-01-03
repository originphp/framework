<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2021 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */
declare(strict_types = 1);
namespace Origin\Model\Schema;

use Origin\Model\ConnectionManager;
use Origin\Core\Exception\Exception;

/**
 * Migrations - This is designed for editing the schema, sometimes data might need to modified but
 * it should not be used to insert data. (if you have too then use connection manager)
 * There are suttle changes here, so this cannot be just droped in model driver. E.g. decimal and numeric does not have limit
 */

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
     * This is required for renaming columns
     *
     * @var array
     */
    protected $typeMap = [
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
     * This describes the table in the database using the new format. This will require caching due to the amount
     * of queries that will need to be executed.
     *
     * @internal this is the new function, will evenutally replace schema
     *
     * @param string $table
     * @return array
     */
    public function describe(string $table): array
    {
        $results = $this->fetchAll('SHOW FULL COLUMNS FROM ' . $this->quoteIdentifier($table));
        $columns = $this->convertTableDescription($results);

        $indexes = $constraints = [];

        # Convert constraints

        foreach ($this->indexes($table) as $index) {
            $name = $index['name'];
            if ($index['name'] === 'PRIMARY') {
                $constraints['primary'] = ['type' => 'primary','column' => $index['column']];
            } elseif ($index['type'] === 'unique') {
                $constraints[$name] = ['type' => 'unique','column' => $index['column']];
            } else {
                $indexes[$name] = ['type' => $index['type'],'column' => $index['column']];
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
            'collate' => $row['Collation'],
        ];

        return ['columns' => $columns,'constraints' => $constraints,'indexes' => $indexes, 'options' => $options];
    }

    /**
     * Converts the results from the describeTableSQL to abstract
     *
     * @param array $data
     * @return array
     */
    protected function convertTableDescription(array $data): array
    {
        $out = [];
    
        foreach ($data as $row) {
            $definition = $this->parseColumn($row['Type']);
            $definition += [
                'null' => $row['Null'] === 'YES'?true:false,
                'default' => $this->defaultvalue($definition['type'], $row['Default']),
            ];
            if (! empty($row['Collation'])) {
                $definition['collate'] = $row['Collation'];
            }
            if (! empty($row['Comment'])) {
                $definition['comment'] = $row['Comment'];
            }

            if (isset($row['Extra']) && $row['Extra'] === 'auto_increment') {
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
    protected function parseColumn(string $column): array
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
            if (isset($matches[2]) && strpos($matches[2], ',') !== false) {
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

        if ($col === 'tinyint' && $limit === 1) {
            return ['type' => 'boolean','null' => false];
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
    protected function columnSql(array $data): string
    {
        $out = $this->quoteIdentifier($data['name']);

        /**
         * Work with mapped or custom types
         */
        $type = $data['type'];
        $isMapped = isset($this->typeMap[$data['type']]);
        if ($isMapped) {
            $type = $this->typeMap[$data['type']];
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
            if (! empty($data['limit']) && in_array($data['limit'], $this->columnLimits)) {
                $key = array_search($data['limit'], $this->columnLimits);
                $type = strtoupper($key);
            }
        } elseif ($data['type'] === 'boolean') {
            $type = 'TINYINT(1)';
            $data['null'] = false;
        }

        $out .= ' ' . $type;

        // deal with types that have limits or custom types with limit set
        if (! empty($data['limit']) && (in_array($data['type'], ['integer','string','bigint']) || ! $isMapped)) {
            $out .= '(' . $data['limit'] . ')';
        } elseif (! empty($data['precision']) && (in_array($data['type'], ['decimal','float']) || ! $isMapped)) {
            $out .= '(' . $data['precision'] .',' . ($data['scale'] ?? 0) . ')'; // 0 is MySQL default
        }

        // deal with unsigned
        if (in_array($data['type'], ['integer','bigint','decimal','float']) && ! empty($data['unsigned'])) {
            $out .= ' UNSIGNED';
        }

        if (in_array($data['type'], ['string','text']) && ! empty($data['collate'])) {
            $out .= ' COLLATE ' . $data['collate'];
        }
        
        if (isset($data['null']) && $data['null'] === false) {
            $out .= ' NOT NULL';
        }

        if ($data['type'] === 'timestamp') {
            if (isset($data['null']) && $data['null'] === true) {
                $out .= ' NULL';
            }
            if (isset($data['default']) && strtolower($data['default']) === 'current_timestamp') {
                $out .= ' DEFAULT CURRENT_TIMESTAMP';
                unset($data['default'],$data['null']);
            }
        }

        if (in_array($data['type'], ['integer','bigint']) && ! empty($data['autoIncrement'])) {
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
    public function indexes(string $table): array
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
    public function renameTable(string $from, string $to): string
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
     * @return array
     */
    public function changeColumn(string $table, string $name, string $type, array $options = []): array
    {
        $options += ['name' => $name, 'type' => $type];

        $sql = sprintf(
            'ALTER TABLE %s MODIFY COLUMN %s',
            $this->quoteIdentifier($table),
            $this->columnSql($options)
        );

        return [$sql];
    }

    /**
     * Returns a rename column SQL statement
     * @internal Changed to be compatable with older versions of MySQL e.g 5.x
     * @param string $table
     * @param string $from
     * @param string $to
     * @return array
     */
    public function renameColumn(string $table, string $from, string $to): array
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
            $definition = $this->typeMap[$schema['type']];
            if (! empty($schema['limit'])) {
                $definition .= "({$schema['limit']})";
            } elseif (in_array($schema['type'], ['float','decimal'])) {
                $definition .= "({$schema['precision']},{$schema['scale']})";
            }
        }

        $sql = sprintf(
            'ALTER TABLE %s CHANGE %s %s %s',
            $this->quoteIdentifier($table),
            $this->quoteIdentifier($from),
            $this->quoteIdentifier($to),
            $definition
        );

        return [$sql];
    }

    /**
     * Returns a remove index SQL statement
     *
     * @see https://dev.mysql.com/doc/refman/8.0/en/drop-index.html
     * @param string $table
     * @param string $name
     * @return string
     */
    public function removeIndex(string $table, string $name): string
    {
        return sprintf(
            'DROP INDEX %s ON %s',
            $this->quoteIdentifier($name),
            $this->quoteIdentifier($table)
        );
    }

    /**
     * Renames an index
     * @requires MySQL 5.7+
     *
     * @param string $table
     * @param string $oldName
     * @param string $newName
     * @return array
     */
    public function renameIndex(string $table, string $oldName, string $newName): array
    {
        $sql = sprintf(
            'ALTER TABLE %s RENAME INDEX %s TO %s',
            $this->quoteIdentifier($table),
            $this->quoteIdentifier($oldName),
            $this->quoteIdentifier($newName)
        );

        return [$sql];
    }

    /**
     * Sql for truncating a table
     *
     * @param string $table
     * @return array
     */
    public function truncateTableSql(string $table): array
    {
        $sql = sprintf('TRUNCATE TABLE %s', $this->quoteIdentifier($table));

        return [$sql];
    }

    public function changeAutoIncrementSql(string $table, string $column, int $counter): string
    {
        return sprintf('ALTER TABLE %s AUTO_INCREMENT = %d', $this->quoteIdentifier($table), $counter);
    }

    /**
     * Sql for disabling foreign key checks
     *
     * @return string
     */
    public function disableForeignKeySql(): string
    {
        return 'SET FOREIGN_KEY_CHECKS = 0';
    }

    /**
     * Sql for enabling foreign key checks
     *
     * @return string
     */
    public function enableForeignKeySql(): string
    {
        return 'SET FOREIGN_KEY_CHECKS = 1';
    }

    /**
     * Returns a list of foreign keys on a table
     *
     * @param string $table
     * @return array
     */
    public function foreignKeys(string $table): array
    {
        $config = ConnectionManager::config($this->datasource);

        //SELECT TABLE_NAME as 'table',COLUMN_NAME as 'column',CONSTRAINT_NAME as 'name', REFERENCED_TABLE_NAME as 'referencedTable',REFERENCED_COLUMN_NAME as 'referencedColumn' FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_SCHEMA = 'origin' AND TABLE_NAME = 'bookmarks'

        $sql = sprintf(
            "SELECT 
            s1.TABLE_NAME AS 'table',
            s1.COLUMN_NAME AS 'column',
            s1.CONSTRAINT_NAME AS 'name', 
            s1.REFERENCED_TABLE_NAME AS 'referencedTable',
            s1.REFERENCED_COLUMN_NAME AS 'referencedColumn',
            c1.UPDATE_RULE as 'update',
            c1.DELETE_RULE as 'delete'
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS s1 
        INNER JOIN information_schema.referential_constraints AS c1
        WHERE 
            REFERENCED_TABLE_SCHEMA = %s AND 
            s1.CONSTRAINT_NAME = c1.CONSTRAINT_NAME AND 
            s1.CONSTRAINT_SCHEMA = c1.CONSTRAINT_SCHEMA AND 
            s1.TABLE_NAME = %s",
            $this->schemaValue($config['database']),
            $this->schemaValue($table)
        );

        $actionMap = [
            'CASCADE' => 'cascade',
            'RESTRICT' => 'restrict',
            'NO ACTION' => 'noAction',
            'SET NULL' => 'setNull',
            'SET DEFAULT' => 'setDefault'
        ];
 
        $out = [];
        foreach ($this->fetchAll($sql) as $result) {
            $out[] = [
                'name' => $result['name'],
                'table' => $table,
                'column' => $result['column'],
                'referencedTable' => $result['referencedTable'],
                'referencedColumn' => $result['referencedColumn'],
                'update' => $actionMap[$result['update']],
                'delete' => $actionMap[$result['delete']],
            ];
        }

        return $out;
    }
    /**
     * Returns a remove foreignKey constraint SQL statement
     *
     * @param string $fromTable
     * @param string $constraint
     * @return array
     */
    public function removeForeignKey(string $fromTable, string $constraint): array
    {
        $sql = sprintf(
            'ALTER TABLE %s DROP FOREIGN KEY %s',
            $this->quoteIdentifier($fromTable),
            $this->quoteIdentifier($constraint)
        );

        return [$sql];
    }
    /**
     * Undocumented function
     *
     * @param string $table
     * @return string
     */
    public function showCreateTable(string $table): string
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
    public function createTableSql(string $table, array $schema, array $options = []): array
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
        $databaseOptions = $options['options'] ?? [];

        if (isset($options['options']['autoIncrement']) && isset($options['constraints']['primary']['column'])) {
            if (is_string($options['constraints']['primary']['column'])) {
                $databaseOptions['setAutoIncrement'] = $this->changeAutoIncrementSql(
                    $table,
                    $options['constraints']['primary']['column'],
                    $options['options']['autoIncrement']
                );
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
    protected function buildCreateTableSql(string $table, array $columns, array $constraints, array $indexes, array $options = []): array
    {
        $out = sprintf(
            "CREATE TABLE %s (\n%s\n)",
            $this->quoteIdentifier($table),
            implode(",\n", array_merge($columns, $constraints, $indexes))
        );

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
        
        $out = [$out];
        if (isset($options['setAutoIncrement'])) {
            $out[] = $options['setAutoIncrement'];
        }

        return $out;
    }

    /**
    * This creates a foreignKey table parameter
    *
    * @param array $attributes name,columns,references, update,delete
    * @return string
    */
    protected function tableConstraintForeign(array $attributes): string
    {
        $sql = sprintf(
            'CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s (%s)',
            $this->quoteIdentifier($attributes['name']),
            implode(', ', (array) $attributes['column']),
            $this->quoteIdentifier($attributes['references'][0]),
            $attributes['references'][1]
        );
        
        if (! empty($attributes['update']) || ! empty($attributes['delete'])) {
            $sql .= ' ' . sprintf('ON UPDATE %s ON DELETE %s', $this->onClause($attributes['update']), $this->onClause($attributes['delete']));
        }

        return $sql;
    }

    /**
    * Creates the constraint code
    *
    * @param array $attributes
    * @return string
    */
    protected function tableConstraint(array $attributes): string
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
    protected function tableIndex(array $attributes): string
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
        if ($value === '' || $value === null) {
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
    * @param array $options (ifExists)
    * @return string
    */
    public function dropTableSql(string $table, array $options = []): string
    {
        $sql = 'DROP TABLE %s';
        if (! empty($options['ifExists'])) {
            $sql = 'DROP TABLE IF EXISTS %s';
        }

        return sprintf($sql, $this->quoteIdentifier($table));
    }
}
