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
declare(strict_types = 1);
namespace Origin\Model\Schema;

use InvalidArgumentException;
use Origin\Core\Exception\Exception;

/**
 * Sqlite is a bit problametic as it is missing many Sql features or has is more restricted, for example
 * foreign keys can only be added at the time of table creation, Concat not supported. Can set the autoIncrement value
 * with no records in the database, are just some of the problems encountered.
 */

class SqliteSchema extends BaseSchema
{
    /**
     * The quote character for table names and columns
     *
     * @var string
     */
    protected $quote = '"';

    /**
     * This is required for renaming columns
     *
     * @var array
     */
    protected $typeMap = [
        'string' => 'VARCHAR',
        'text' => 'TEXT',
        'integer' => 'INTEGER',
        'bigint' => 'BIGINT',
        'float' => 'FLOAT',
        'decimal' => 'DECIMAL',
        'datetime' => 'DATETIME',
        'date' => 'DATE',
        'time' => 'TIME',
        'timestamp' => 'TIMESTAMP',
        'binary' => 'BLOB',
        'boolean' => 'BOOLEAN',
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
        $results = $this->fetchAll('PRAGMA table_info(' . $this->quoteIdentifier($table) .  ')');
    
        $isAutoincrement = (bool) $this->fetchRow('SELECT count(*) FROM sqlite_master WHERE tbl_name="' . $table . '" AND sql LIKE "%AUTOINCREMENT%"');
       
        $indexes = $constraints = [];

        # Convert constraints
        foreach ($this->indexes($table) as $index) {
            $name = $index['name'];
            // this constraint is added in convertTableDescription
            if (substr($name, 0, 16) === 'sqlite_autoindex') {
                continue;
            }
      
            if ($index['type'] === 'unique') {
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

        list($columns, $constraints) = $this->convertTableDescription($results, $constraints, $isAutoincrement);

        $options = [];

        return ['columns' => $columns,'constraints' => $constraints,'indexes' => $indexes, 'options' => $options];
    }

    /**
     * Converts the results from the describeTableSQL to abstract
     *
     * @internal to get autoincrement field need to look at constrains
     *
     * @param array $data
     * @param array $constraints
     * @param boolean $isAutoIncrement
     * @return array
     */
    protected function convertTableDescription(array $data, array $constraints, bool $isAutoIncrement): array
    {
        $out = [];
    
        foreach ($data as $row) {
            $definition = $this->parseColumn($row['type']);

            $definition += [
                'null' => ! $row['notnull'],
                'default' => $this->defaultvalue($definition['type'], $row['dflt_value']),
            ];
           
            if ($row['pk'] && $isAutoIncrement) {
                $definition['null'] = false;
                $definition['autoIncrement'] = true;
                $constraints['primary'] = ['type' => 'unique','column' => $row['name']]; // must be string here to set autoincrement
            }

            $out[$row['name']] = $definition;
        }

        return [$out, $constraints];
    }

    /**
     * Parses column data
     *
     * @see https://www.sqlite.org/datatype3.html
     *
     * @param string $column e.g int(11) unsigned
     * @return array
     */
    protected function parseColumn(string $column): array
    {
        preg_match('/(unsigned )?([a-z]+)(?:\(([0-9,]+)\))*/i', $column, $matches); //'unsigned int(10)'
        if (empty($matches)) {
            throw new Exception(sprintf('Error parsing %s', $column));
        }

        $unsigned = substr(strtolower($column), 0) === 'unsigned';

        $limit = $precision = $scale = null;

        $col = strtolower($matches[2]);
        if (isset($matches[3])) {
            $limit = (int) $matches[3]; // convert to int
        }

        if ($col === 'varchar') {
            return ['type' => 'string','limit' => $limit];
        }

        if ($col === 'integer') {
            return ['type' => 'integer','limit' => $limit,'unsigned' => $unsigned];
        }
        /**
         * Handle floats and decimals. map doubles to decimal
         */
        if (in_array($col, ['float','decimal','double'])) {
            if (isset($matches[3]) && strpos($matches[3], ',') !== false) {
                list($precision, $scale) = explode(',', $matches[3]);
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
        } elseif ($data['type'] === 'boolean') {
            $type = 'TINYINT(1)';
            $data['null'] = false;
        }

        $out .= ' ' . $type;

        // deal with types that have limits or custom types with limit set
        if (! empty($data['limit']) && (in_array($data['type'], ['string','bigint']) || ! $isMapped)) {
            $out .= '(' . $data['limit'] . ')';
        } elseif (! empty($data['precision']) && (in_array($data['type'], ['decimal','float']) || ! $isMapped)) {
            $out .= '(' . $data['precision'] .',' . ($data['scale'] ?? 0) . ')'; // 0 is MySQL default
        }

        // deal with unsigned
        if (in_array($data['type'], ['integer','bigint','decimal','float']) && ! empty($data['unsigned'])) {
            $out .= ' UNSIGNED';
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
            $out .= ' PRIMARY KEY AUTOINCREMENT';
        } elseif (isset($data['default'])) {
            $out .= ' DEFAULT ' . $this->schemaValue($data['default']);
        }
        
        if (! empty($data['comment'])) {
            $out .= ' /* ' . $data['comment'] . ' */';
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
        $sql = sprintf('PRAGMA index_list(%s)', $this->quoteIdentifier($table));

        $results = $this->fetchAll($sql);
  
        $indexes = [];

        foreach ($results as $result) {
            $sql = sprintf('PRAGMA index_info(%s)', $this->quoteIdentifier($result['name']));
            $columns = [];
            foreach ($this->fetchAll($sql) as $column) {
                $columns[] = $column['name'];
            }

            $indexes[] = [
                'name' => $result['name'],
                'column' => $columns,
                'type' => $result['unique'] ? 'unique' : 'index',
            ];
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
            'ALTER TABLE %s RENAME TO %s',
            $this->quoteIdentifier($from),
            $this->quoteIdentifier($to)
        );
    }

    /**
     * Changes a column according to the new definition.
     *
     * @param string $table
     * @param string $name
     * @param array $options
     * @return array
     */
    public function changeColumn(string $table, string $name, string $type, array $options = []): array
    {
        $out = [];
        // store adjusted schema for future calls
        $schema = $this->describe($table);

        $schema['columns'][$name] = $options + ['type' => $type, 'null' => true,'default' => null];
      
        // Drop all indexes
        $out = [];
        foreach ($schema['indexes'] as $index => $defintion) {
            $out[] = $this->removeIndex($table, $index);
        }
        $out = array_merge($out, $this->createTableSql($table, $schema['columns'], $schema));

        #$out = $this->createTableSql($table, $schema['columns'], $schema);

        array_unshift($out, $this->renameTable($table, 'schema_tmp'));
        $out[] = sprintf('INSERT INTO %s SELECT * FROM schema_tmp', $this->quoteIdentifier($table));
        $out[] = $this->dropTableSql('schema_tmp');

        return $out;
    }

    /**
     * Returns a rename column SQL statements (does not handle indexes or constraints)
     *
     * @param string $table
     * @param string $from
     * @param string $to
     * @param array $schema
     * @return array
     */
    public function renameColumn(string $table, string $from, string $to, array $schema = null): array
    {
        $out = [];

        if ($schema === null) {
            $schema = $this->describe($table);
        }

        if (! isset($schema['columns'][$from])) {
            throw new InvalidArgumentException(sprintf('Column %s does not exist', $from));
        }
       
        // rename column in schema
        $schema['columns'][$to] = $schema['columns'][$from];
        unset($schema['columns'][$from]);

        // rename constraints
        foreach ($schema['constraints'] as $constraint => $definition) {
            if ($definition['column'] === $from) {
                $schema['constraints'][$constraint]['column'] = $to;
            } elseif (is_array($definition['column']) && in_array($from, $definition['column'])) {
                $key = array_search($from, $definition['column']);
                $schema['constraints'][$constraint]['column'][$key] = $to;
            }
        }
        
        /**
         * Mysql does not rename indexes, so this wont either
         */
        foreach ($schema['indexes'] as $index => $definition) {
            if ($definition['column'] === $from) {
                $schema['indexes'][$index]['column'] = $to;
            } elseif (is_array($definition['column']) && in_array($from, $definition['column'])) {
                $key = array_search($from, $definition['column']);
                $schema['indexes'][$index]['column'][$key] = $to;
            }
        }

        // Drop all indexes
        $out = [];
        foreach ($schema['indexes'] as $index => $defintion) {
            $out[] = $this->removeIndex($from, $index);
        }
        $out = array_merge($out, $this->createTableSql($table, $schema['columns'], $schema));

        #$out = $this->createTableSql($table, $schema['columns'], $schema);

        array_unshift($out, $this->renameTable($table, 'schema_tmp'));
        $out[] = sprintf('INSERT INTO %s SELECT * FROM schema_tmp', $this->quoteIdentifier($table));
        $out[] = $this->dropTableSql('schema_tmp');

        return $out;
    }

    /**
    * Removes a column from the table
    *
    * @param string $table
    * @param string $column
    * @param array $schema optionally pass an array of schema to use
     * @return array
     */
    public function removeColumn(string $table, string $column, array $schema = null): array
    {
        return $this->removeColumns($table, [$column], $schema);
    }

    /**
     * Removes multiple columns from the table (does not remove indexes or constraints)
     *
     * @param string $table
     * @param array $columns
     * @param array $schema optionally pass an array of schema to use
     * @return array
     */
    public function removeColumns(string $table, array $columns, array $schema = null): array
    {
        if ($schema === null) {
            $schema = $this->describe($table);
        }
        
        foreach ($columns as $column) {
            if (isset($schema['columns'][$column])) {
                unset($schema['columns'][$column]);
            }
        }

        $out = $this->createTableSql($table, $schema['columns'], $schema);
        array_unshift($out, $this->renameTable($table, 'schema_tmp'));

        $fields = implode(', ', array_keys($schema['columns']));

        $out[] = sprintf('INSERT INTO %s SELECT %s FROM schema_tmp', $this->quoteIdentifier($table), $fields);
        $out[] = $this->dropTableSql('schema_tmp');

        return $out;
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
            'DROP INDEX %s',
            $this->quoteIdentifier($name)
        );
    }

    /**
     * Renames an index
     *
     * @param string $table
     * @param string $oldName
     * @param string $newName
     * @param array $schema
     * @return array
     */
    public function renameIndex(string $table, string $oldName, string $newName, array $schema = null): array
    {
        if ($schema === null) {
            $schema = $this->describe($table);
        }

        if (! isset($schema['indexes'][$oldName])) {
            throw new InvalidArgumentException(sprintf('Index %s does not exist', $oldName));
        }
     
        $index = $schema['indexes'][$oldName];

        $out = [];
        $out[] = $this->removeIndex($table, $oldName);
        $out[] = $this->addIndex($table, $index['column'], $newName, ['unique' => $index['unique'] ?? false]);

        return $out;
    }

    /**
     * Sql for truncating a table
     *
     * @param string $table
     * @return array
     */
    public function truncateTableSql(string $table): array
    {
        $out = [];
        
        $out[] = sprintf('DELETE from sqlite_sequence WHERE name = %s', $this->quoteIdentifier($table));
        $out[] = sprintf('DELETE FROM %s', $this->quoteIdentifier($table));

        return $out;
    }

    /**
     * Changes the autoincrement number. Due to how sqlite works, you can't set the number to start with, unless
     * you create a record, set the number then delete the record.
     *
     * @param string $table
     * @param string $column
     * @param integer $counter
     * @return string
     */
    public function changeAutoIncrementSql(string $table, string $column, int $counter): string
    {
        return sprintf('UPDATE SQLITE_SEQUENCE SET seq = %d WHERE name = %s', $counter, $this->quoteIdentifier($table));
    }

    /**
     * Sql for disabling foreign key checks
     *
     * @return string
     */
    public function disableForeignKeySql(): string
    {
        return 'PRAGMA foreign_keys = OFF';
    }

    /**
     * Sql for enabling foreign key checks
     *
     * @return string
     */
    public function enableForeignKeySql(): string
    {
        return 'PRAGMA foreign_keys = ON';
    }

    /**
     * Returns a list of foreign keys on a table.
     *
     * @internal There is no way to get foreignkey name in sqlite
     *
     * @param string $table
     * @return array
     */
    public function foreignKeys(string $table): array
    {
        $sql = sprintf('PRAGMA foreign_key_list(%s)', $this->quoteIdentifier($table));

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
                'name' => 'fk_origin_' . hash('crc32', $table . '__' . $result['from']),
                'table' => $table,
                'column' => $result['from'],
                'referencedTable' => $result['table'],
                'referencedColumn' => $result['to'],
                'update' => $actionMap[$result['on_update']],
                'delete' => $actionMap[$result['on_delete']]
                //'match' => $result['match']
            ];
        }

        return $out;
    }
    /**
     * Sqlite does not support adding or removing foreign keys on existing tables
     *
     * @param string $fromTable
     * @param string $constraint
     * @param array $schema
     * @return array
     */
    public function removeForeignKey(string $fromTable, string $constraint, array $schema = null): array
    {
        if ($schema === null) {
            $schema = $this->describe($fromTable);
        }
       
        if (! isset($schema['constraints'][$constraint])) {
            throw new InvalidArgumentException(sprintf('Constraint %s does not exist', $constraint));
        }
        unset($schema['constraints'][$constraint]);

        // Drop all indexes
        $out = [];
        foreach ($schema['indexes'] as $index => $defintion) {
            $out[] = $this->removeIndex($fromTable, $index);
        }
        $out = array_merge($out, $this->createTableSql($fromTable, $schema['columns'], $schema));
       
        #$out = $this->createTableSql($fromTable, $schema['columns'], $schema);

        array_unshift($out, $this->renameTable($fromTable, 'schema_tmp'));
        $out[] = sprintf('INSERT INTO %s SELECT * FROM schema_tmp', $this->quoteIdentifier($fromTable));
        $out[] = $this->dropTableSql('schema_tmp');

        return $out;
    }
    /**
     * Undocumented function
     *
     * @param string $table
     * @return string
     */
    public function showCreateTable(string $table): string
    {
        $result = $this->fetchRow('SELECT sql from sqlite_master WHERE name = ' . $this->quoteIdentifier($table));

        return $result['sql'];
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
        
        $autoIncrementColumn = null;
        
        foreach ($schema as $name => $definition) {
            if (is_string($definition)) {
                $definition = ['type' => $definition];
            }
            $columns[] = '  ' . $this->columnSql(['name' => $name] + $definition);

            if (! empty($definition['autoIncrement'])) {
                $autoIncrementColumn = $name;
            }
        }
     
        if (isset($options['constraints'])) {
            foreach ($options['constraints'] as $name => $definition) {
                // primary key set for autoincrments in table sql, so dont add again
                if ($definition['type'] === 'primary' && in_array($autoIncrementColumn, (array) $definition['column'])) {
                    continue;
                }
                $constraints[] = '  ' .  $this->tableConstraint(['name' => $name] + $definition);
            }
        }
        
        if (isset($options['indexes'])) {
            foreach ($options['indexes'] as $name => $definition) {
                $indexes[] = $this->tableIndex(['name' => $name,'table' => $table] + $definition);
            }
        }

        if (isset($options['options'])) {
            $databaseOptions = $options['options'];
            /**
             * @deprecated This provides backwards comptability
             */
            if (is_string($databaseOptions)) {
                $databaseOptions = ['options' => $options];
            }
        }

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
        $out = [];
        $definition = implode(",\n", array_merge($columns, $constraints));
        $out[] = sprintf("CREATE TABLE \"%s\" (\n%s\n)", $table, $definition);
        foreach ($indexes as $index) {
            $out[] = $index;
        }

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
            return sprintf('UNIQUE (%s)', $columns);
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
        if (empty($attributes['table'])) {
            throw new Exception(sprintf('No table name provided for index %s', $attributes['name']));
        }
        $string = 'CREATE INDEX %s ON %s (%s)';
        if ($attributes['type'] === 'unique') {
            $string = 'CREATE UNIQUE INDEX %s ON %s (%s)';
        }

        return sprintf(
            $string,
            $this->quoteIdentifier($attributes['name']),
            $this->quoteIdentifier($attributes['table']),
            implode(', ', (array) $attributes['column'])
        );
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

    /**
     * Sqlite does not support adding foreign keys to existing tables
     *
     * @param string $fromTable
     * @param string $name
     * @param string $column
     * @param string $toTable
     * @param string $primaryKey
     * @param string $onUpdate
     * @param string $onDelete
     * @return array
     */
    public function addForeignKey(string $fromTable, string $name, string $column, string $toTable, string $primaryKey, string $onUpdate = null, string $onDelete = null, array $schema = null): array
    {
        if ($schema === null) {
            $schema = $this->describe($fromTable);
        }
       
        $schema['constraints'][$name] = [
            'type' => 'foreign',
            'column' => $column,
            'references' => [$toTable, $primaryKey],
            'update' => $onUpdate,
            'delete' => $onDelete
        ];

        // Drop all indexes
        $out = [];
        foreach ($schema['indexes'] as $index => $defintion) {
            $out[] = $this->removeIndex($fromTable, $index);
        }
        $out = array_merge($out, $this->createTableSql($fromTable, $schema['columns'], $schema));
        
        //$out = $this->createTableSql($fromTable, $schema['columns'], $schema);
        array_unshift($out, $this->renameTable($fromTable, 'schema_tmp'));
        $out[] = sprintf('INSERT INTO %s SELECT * FROM schema_tmp', $this->quoteIdentifier($fromTable));
        $out[] = $this->dropTableSql('schema_tmp');

        return $out;
    }

    /**
    * Standardizes default values
    *
    * @param mixed $value
    * @return mixed
    */
    protected function defaultValue(string $type, $value)
    {
        if ($value === null) {
            return null;
        }
       
        if (in_array($type, ['bigint','integer','float','decimal'])) {
            return ($value == (int) $value) ? (int) $value : (float) $value;
        }

        if ($type === 'boolean') {
            return (bool) $value;
        }
        
        if (in_array($type, ['string','text'])) {
            return trim($value, '\'');
        }

        return $value;
    }
}
