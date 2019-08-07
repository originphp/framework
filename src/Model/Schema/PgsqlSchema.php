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

class PgsqlSchema extends BaseSchema
{
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
        
        # On PostgreSQL indexes are added after creating the name
        if (isset($options['indexes'])) {
            foreach ($options['indexes'] as $name => $definition) {
                $indexes[] = $this->tableIndex(['name' => $name,'table' => $table] + $definition);
            }
        }

        # PostgreSQL column comments
        $comments = [];
        foreach ($schema as $column => $data) {
            if (! empty($data['comment'])) {
                $comments[$column] = $data['comment'];
            }
        }
        $databaseOptions['comments'] = $comments;

        return $this->buildCreateTableSql($table, $columns, $constraints, $indexes, $databaseOptions);
    }

    /**
        * This is the new create Table function
        * @internal on pgsql indexes have to be created outside of the table definition
        *
        * @param string $table
        * @param array $params array of columns with reserved keys indexes, constraints
        * @param array $options (database specific options e.g mysql engine)
        * @return array
        */
    protected function buildCreateTableSql(string $table, array $columns, array $constraints, array $indexes, array $options = []) : array
    {
        $out = $comments = [];
        $definition = implode(",\n", array_merge($columns, $constraints));
        $out[] = sprintf("CREATE TABLE \"%s\" (\n%s\n)", $table, $definition);
        foreach ($indexes as $index) {
            $out[] = $index;
        }
        $tableName = $this->quoteIdentifier($table); // dont run in loop
        
        foreach ($options['comments'] as $column => $comment) {
            $out[] = sprintf(
                'COMMENT ON COLUMN %s.%s IS %s',
                $tableName,
                $this->quoteIdentifier($column),
                $this->schemaValue($comment)
            );
        }

        return $out;
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
        if ($attributes['type'] === 'unique') {
            return sprintf('CONSTRAINT %s UNIQUE (%s)', $this->quoteIdentifier($attributes['name']), $columns);
        }
       
        if ($attributes['type'] === 'foreign') {
            return $this->tableConstraintForeign($attributes);
        }
        throw new Exception(sprintf('Unknown constriant %s', $attributes['type']));
    }

    /**
    * creates the indexes when creating tables. In Postgresql this is the same
    * as add index as this is added after the create table. Eventually the
    * addIndex needs to be re factored to quote identifiers, but not before this task has been
    * completed.
    *
    * @param array $attributes
    * @return string
    */
    protected function tableIndex(array $attributes) : string
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
     * Try to map types
     *
     * @param string $type
     * @return string
     */
    private function column(string $type) : string
    {
        $result = 'string';
        if (in_array($type, ['integer', 'text', 'date', 'time', 'boolean', 'bigint'])) {
            $result = $type;
        }
        // Char and varchar
        elseif (strpos($type, 'character') !== false or $type === 'uuid') {
            $result = 'string';
        } elseif (strpos($type, 'timestamp') !== false) {
            $result = 'datetime';
        } elseif (in_array($type, ['decimal', 'numeric'])) {
            $result = 'decimal';
        } elseif (strpos($type, 'time') !== false) { // time without time zone,with etc
            $result = 'time';
        } elseif (strpos($type, 'bytea') !== false) {
            $result = 'binary';
        } elseif (in_array($type, ['float', 'real', 'double', 'double precision'])) {
            $result = 'float';
        }

        return $result;
    }

    /**
     * Returns an list of indexs for a table
     * @see https://www.postgresql.org/docs/current/view-pg-indexes.html
     *
     * @param string $table
     * @return array
     */
    public function indexes(string $table) : array
    {
        $sql = sprintf(
            'SELECT i.relname AS name, a.attname AS column, ix.indisunique AS unique, ix.indisprimary AS primary FROM pg_class t, pg_class i, pg_index ix, pg_attribute a WHERE t.oid = ix.indrelid AND i.oid = ix.indexrelid AND a.attrelid = t.oid AND a.attnum = ANY (ix.indkey) AND t.relkind = \'r\' AND t.relname = %s ORDER BY t.relname, i.relname',
            $this->schemaValue($table)
        );
        $results = $this->fetchAll($sql);
        $indexes = [];
        
        foreach ($results as $result) {
            /**
             * handle multiple columns
             */
            $key = count($indexes) - 1;
            if ($indexes) {
                if ($indexes[$key]['name'] === $result['name']) {
                    $indexes[$key]['column'] = (array) $indexes[$key]['column'];
                    $indexes[$key]['column'][] = $result['column'];
                    continue;
                }
            }
            
            $indexes[] = [
                'name' => $result['name'],
                'column' => $result['column'],
                'type' => $result['unique'] ? 'unique': 'index',
            ];
        }

        return $indexes;
    }

    /**
     * Returns a rename table SQL stataement
     *
     * @param string $from
     * @param string $to
     * @return string
     */
    public function renameTable(string $from, string $to) : string
    {
        return sprintf(
            'ALTER TABLE %s RENAME TO %s',
            $this->quoteIdentifier($from),
            $this->quoteIdentifier($to)
        );
        //return  "ALTER TABLE {$from} RENAME TO {$to}";
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
        $options += ['default' => null, 'null' => null];
        if (isset($this->columns[$type])) {
            $options = array_merge($this->columns[$type], $options);
            $agnoType = $type;
            $type = $this->columns[$type]['name'];

            if ($agnoType === 'decimal' or $agnoType === 'float') {
                $type = "{$type}({$options['precision']},{$options['scale']})";
            } elseif (in_array($agnoType, ['string']) and ! empty($options['limit'])) {
                $type = "{$type}({$options['limit']})";
            }
        }
        $name = $this->quoteIdentifier($name);

        $sql = sprintf(
            'ALTER TABLE %s ALTER COLUMN %s SET DATA TYPE %s',
            $this->quoteIdentifier($table),
            $name,
            $type
        );
       
        $default = $this->schemaValue($options['default']);
        
        if (! empty($options['default']) and $options['null'] === false) {
            $sql .= ", ALTER COLUMN {$name} SET DEFAULT {$default}, ALTER COLUMN {$name} SET NOT NULL";
        } elseif (isset($options['default'])) {
            $sql .= ", ALTER COLUMN {$name} SET DEFAULT {$default}";
        } elseif ($options['null'] === false) {
            $sql .= ", ALTER COLUMN {$name} SET NOT NULL";
        }

        return $sql;
    }

    /**
     * Returns a rename column SQL stataement
     *
     * @param string $table
     * @param string $from
     * @param string $to
     * @return string
     */
    public function renameColumn(string $table, string $from, string $to) : string
    {
        return sprintf(
            'ALTER TABLE %s RENAME COLUMN %s TO %s',
            $this->quoteIdentifier($table),
            $this->quoteIdentifier($from),
            $this->quoteIdentifier($to)
        );
        //return "ALTER TABLE {$table} RENAME COLUMN {$from} TO {$to}";
    }

    /**
     * Returns a remove index SQL stataement
     *
     * @param string $table
     * @param string|array $column owner_id, [owner_id,tenant_id]
     * @param array $options
     *  - name: name of index
     * @return string
     */
    public function removeIndex(string $table, string $name) : string
    {
        return sprintf(
            'DROP INDEX %s',
            $this->quoteIdentifier($name)
        );
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
    public function renameIndex(string $table, string $oldName, string $newName): string
    {
        return sprintf(
            'ALTER INDEX %s RENAME TO %s',
            $this->quoteIdentifier($oldName),
            $this->quoteIdentifier($newName)
        );
    }

    /**
     * Returns a list of foreign keys for table
     *
     * @param string $table
     * @return array
     */
    public function foreignKeys(string $table) : array
    {
        $config = ConnectionManager::config($this->datasource);

        // $sql = 'SELECT tc.table_name, kcu.column_name as column_name,tc.constraint_name AS constraint_name, ccu.table_name AS referenced_table_name, ccu.column_name AS referenced_column_name FROM information_schema.table_constraints AS tc JOIN information_schema.key_column_usage AS kcu ON tc.constraint_name = kcu.constraint_name JOIN information_schema.constraint_column_usage AS ccu ON ccu.constraint_name = tc.constraint_name WHERE  tc.table_catalog = \'' . $this->connection()->database() . '\' AND  tc.table_name = \'' . $table . '\' AND tc.table_schema = \'public\' AND tc.constraint_type = \'FOREIGN KEY\'';

        $sql = sprintf(
            'SELECT tc.table_name, kcu.column_name as column_name,tc.constraint_name AS constraint_name, ccu.table_name AS referenced_table_name, ccu.column_name AS referenced_column_name FROM information_schema.table_constraints AS tc JOIN information_schema.key_column_usage AS kcu ON tc.constraint_name = kcu.constraint_name JOIN information_schema.constraint_column_usage AS ccu ON ccu.constraint_name = tc.constraint_name WHERE  tc.table_catalog = %s AND  tc.table_name = %s AND tc.table_schema = \'public\' AND tc.constraint_type = \'FOREIGN KEY\'',
            $this->schemaValue($this->connection()->database()),
            $this->schemaValue($table)
         );
        $out = [];

        foreach ($this->fetchAll($sql) as $result) {
            $out[] = [
                'name' => $result['constraint_name'],
                'table' => $result['table_name'],
                'column' => $result['column_name'],
                'referencedTable' => $result['referenced_table_name'],
                'referencedColumn' => $result['referenced_column_name'],
            ];
        }

        return $out;
    }
    /**
    * Returns a remove foreign key SQL stataement
     *
     * @param string $fromTable
     * @param string $constraint
     * @return string
     */
    public function removeForeignKey(string $fromTable, string $constraint): string
    {
        return sprintf(
            'ALTER TABLE %s DROP CONSTRAINT %s',
            $this->quoteIdentifier($fromTable),
            $this->quoteIdentifier($constraint)
        );
    }

    /**
     * PGSQL does have this feature. Using pg_dump can be an issue if the db server is a different
     * version like in docker, where a mismatch can occur.
     *
     * @param string $table
     * @return string
     */
    public function showCreateTable(string $table): string
    {
        $schema = $this->connection()->describe($table);

        return implode(";\n", $this->createTableSql($table, $schema['columns'], [
            'constraints' => $schema['constraints'],
            'indexes' => $schema['indexes'],
        ])) .';';
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
            if ($value == true) {
                return 'TRUE';
            }

            return 'FALSE';
        }
        /**
         * For booleans as integers, you need to pass '0' not just 0.
         */
        if (is_numeric($value)) {
            return $value;
        }

        return "'{$value}'";
    }

    /**
    * Returns a SQL statement for dropping a table
    * @internal on pgsql cascade is required for dropping tables if foreign keys reference it
    * @param string $table
    * @param array options (ifExists)
    * @return string
    */
    public function dropTable(string $table, array $options = []) : string
    {
        $sql = 'DROP TABLE %s CASCADE';
        if (! empty($options['ifExists'])) {
            $sql = 'DROP TABLE IF EXISTS %s CASCADE';
        }

        return sprintf($sql, $this->quoteIdentifier($table));
    }

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
        $database = $this->connection()->database();

        $sql = "SELECT DISTINCT
        column_name AS name,
        data_type AS type,
        is_nullable AS null,
        column_default AS default,
        character_maximum_length AS limit,
        numeric_precision as precision,
        numeric_scale as scale,
        pg_get_serial_sequence(attr.attrelid::regclass::text, attr.attname) IS NOT NULL AS autoincrement,
        c.collation_name as collation,
        d.description as comment,
        ordinal_position as position 
     FROM information_schema.columns c 
        INNER JOIN pg_catalog.pg_namespace ns ON (ns.nspname = table_schema) 
        INNER JOIN pg_catalog.pg_class cl ON (cl.relnamespace = ns.oid AND cl.relname = table_name) 
        LEFT JOIN pg_catalog.pg_index i  ON (i.indrelid = cl.oid  AND i.indkey[0] = c.ordinal_position) 
        LEFT JOIN pg_catalog.pg_description d on (cl.oid = d.objoid AND d.objsubid = c.ordinal_position) 
        LEFT JOIN pg_catalog.pg_attribute attr ON (cl.oid = attr.attrelid AND column_name = attr.attname) 
     WHERE table_name = '{$table}' AND table_schema = 'public' AND table_catalog = '{$database}' 
     ORDER BY position";

        $results = $this->fetchAll($sql);
     
        $columns = $this->convertTableDescription($results);

        $indexes = $constraints = [];
     
        /**
         * Convert primary key to constraint
         */
        foreach ($this->indexes($table) as $index) {
            if (substr($index['name'], -5) === '_pkey') {
                $constraints['primary'] = ['type' => 'primary','column' => $index['column']];
                continue;
            }
            $name = $index['name'];
            // unique constraint is same as unique index
            if ($index['type'] === 'unique') {
                $constraints[$name] = ['type' => 'unique','column' => $index['column']];
                continue;
            }
            $indexes[$name] = ['type' => 'index','column' => $index['column']];
        }

        foreach ($this->foreignKeys($table) as $foreignKey) {
            $name = $foreignKey['name'];
            $constraints[$name] = [
                'type' => 'foreign',
                'column' => $foreignKey['column'],
                'references' => [$foreignKey['referencedTable'],$foreignKey['referencedColumn']],
            ];
        }
   
        return ['columns' => $columns,'constraints' => $constraints,'indexes' => $indexes];
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
            'integer' => 'INTEGER',
            'bigint' => 'BIGINT',
            'float' => 'FLOAT',
            'decimal' => 'DECIMAL',
            'datetime' => 'TIMESTAMP',
            'date' => 'DATE',
            'time' => 'TIME',
            'timestamp' => 'TIMESTAMP',
            'binary' => 'BYTEA',
            'boolean' => 'BOOLEAN',
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
        }

        if (in_array($data['type'], ['integer','bigint']) and ! empty($data['autoIncrement'])) {
            $type = $data['type'] === 'integer'?'SERIAL':'BIGSERIAL';
            unset($data['default'],$data['null']);
            // serial is the equivelent of `id INTEGER NOT NULL DEFAULT nextval('table_name_id_seq')`
        }

        $out .= ' ' . $type;

        // deal with types that have limits or custom types with limit set
        if (! empty($data['limit']) and in_array($data['type'], ['string','text']) or ! $isMapped) {
            $out .= '(' . $data['limit'] . ')';
        } elseif (! empty($data['precision'])) {
            if ($data['type'] === 'float') {
                $out .= '(' . $data['precision'] . ')';
            } elseif ($data['type'] === 'decimal' or ! $isMapped) {
                $out .= '(' . $data['precision'] .',' . ($data['scale'] ?? 0) . ')';
            }
        }

        if (in_array($data['type'], ['string','text']) and ! empty($data['collate'])) {
            $out .= ' COLLATE "' . $data['collate'] .'"';
        }

        if (isset($data['null']) and $data['null'] === false) {
            $out .= ' NOT NULL';
        }

        if (isset($data['default']) and $data['type'] === 'timestamp' and strtolower($data['default']) === 'current_timestamp') {
            $out .= ' DEFAULT CURRENT_TIMESTAMP';
        } elseif (isset($data['default'])) {
            $out .= ' DEFAULT ' . $this->schemaValue($data['default']);
        }
        
        return $out;
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
            $defintion = $this->parseColumn($row);
            $defintion += [
                'null' => $row['null'] === 'YES'?true:false,
                'default' => $row['default'],
            ];

            // e.g. default value 'home'::character varying
            if ($row['default']) { // text,varchar and character fields
                if (preg_match("/'(.*?)'::(text|character varying|bpchar)/", $row['default'], $matches)) {
                    $defintion['default'] = $matches[1];
                } elseif (substr($row['default'], 0, 8) === 'nextval(' or substr($row['default'], 0, 6) === 'NULL::') {
                    $defintion['default'] = null;
                }
            }

            if (! empty($row['collation'])) {
                $defintion['collate'] = $row['collation'];
            }
           
            if (! empty($row['comment'])) {
                $defintion['comment'] = $row['comment'];
            }

            // case is correct
            if (! empty($row['autoincrement'])) {
                $defintion['autoIncrement'] = true;
            }
          
            $out[$row['name']] = $defintion;
        }

        return $out;
    }

    /**
     * Parses the column data, the data
     *
     * @param array $row
     * @return array
     */
    protected function parseColumn(array $row) : array
    {
        $out = [];
        $col = $row['type'];
        if (strpos($col, ' ') !== false) {
            list($col, $columnData) = explode(' ', $col, 2);
        }

        if ($row['type'] === 'character varying') {
            return ['type' => 'string','limit' => $row['limit']];
        }

        if ($col === 'character') {
            return ['type' => 'string','limit' => $row['limit'],'fixed' => true];
        }

        if ($col === 'integer' or $col == 'bigint') {
            return ['type' => $col,'limit' => $row['precision']];
        }

        if ($col === 'real') {
            return ['type' => 'float']; // float is bytes not char length
        }

        if ($col === 'numeric') {
            return ['type' => 'decimal','precision' => $row['precision'],'scale' => $row['scale']];
        }

        if (in_array($col, ['boolean','date','text','time',])) {
            return ['type' => $col];
        }

        if ($col === 'timestamp') {
            return ['type' => 'datetime'];
        }

        if ($col === 'bytea') {
            return ['type' => 'binary'];
        }

        return ['type' => 'string'];
    }

    # # # CODE HERE WILL BE DEPRECATED IN FUTURE # # #

    /**
        * This is the map for database agnostic, if its not found here then
        * use what user supplies. @important. This will allow using char and medium text when testing
        *
        * @var array
        */
    protected $columns = [
        'primaryKey' => ['name' => 'SERIAL NOT NULL'],
        'string' => ['name' => 'VARCHAR', 'limit' => 255],
        'text' => ['name' => 'TEXT'],
        'integer' => ['name' => 'INTEGER'],
        'bigint' => ['name' => 'BIGINT'],
        'float' => ['name' => 'FLOAT'],  # Floats get complicated, float(2) converts to real(24).
        'decimal' => ['name' => 'DECIMAL', 'precision' => 10, 'scale' => 0],
        'datetime' => ['name' => 'TIMESTAMP'],
        'timestamp' => ['name' => 'TIMESTAMP'],
        'date' => ['name' => 'DATE'],
        'time' => ['name' => 'TIME'],
        'binary' => ['name' => 'BYTEA'],
        'boolean' => ['name' => 'BOOLEAN'],
    ];

    /**
    * Gets the schema
    * @internal postgre
    * @param string $table
    * @return array
    * @see SELECT * from information_schema.columns WHERE table_catalog = 'origin_test'  AND table_name = 'articles' AND table_schema = 'public'
    */
    public function schema(string $table) : array
    {
        $sql = 'SELECT DISTINCT column_name AS name, data_type AS type, character_maximum_length AS "char_length",numeric_precision ,numeric_scale , column_default AS default,  is_nullable AS "null",character_octet_length AS oct_length, ordinal_position AS position FROM information_schema.columns
        WHERE table_catalog = \'' . $this->connection()->database() . '\' AND  table_name = \'' . $table . '\' AND table_schema = \'public\'  ORDER BY position';

        $schema = [];

        if ($results = $this->fetchAll($sql)) {
       
            /**
             * @todo defaults should be type,length,default,null (remove length if empty)
             */

            foreach ($results as $result) {
                $data = ['type' => null, 'limit' => null, 'default' => null, 'null' => null];
                $data['type'] = $this->column($result['type']);
                if ($data['type'] === 'string' and $result['type'] === 'character varying') {
                    $data['limit'] = $result['char_length'];
                } elseif (in_array($data['type'], ['decimal', 'float'])) {
                    if ($result['numeric_precision']) {
                        $data['precision'] = $result['numeric_precision'];
                    }
                    if ($result['numeric_scale']) {
                        $data['scale'] = $result['numeric_scale'];
                    }
                }

                $data['null'] = ($result['null'] === 'YES' ? true : false);
                //nextval
                $position = strpos($result['default'], '::character varying');
                $isAuto = (strpos($result['default'], 'nextval') !== false);
                if ($position !== false and ! $isAuto) {
                    $data['default'] = trim(substr($result['default'], 0, $position), "'"); // parse 'foo'::character varying
                } elseif (! empty($result['default']) and ! $isAuto) {
                    $data['default'] = $result['default'];
                }

                if ($isAuto) {
                    $data['autoIncrement'] = true;
                }

                /**
                 * Detect Primary Key
                 * @see SELECT * from information_schema.columns WHERE table_catalog = 'origin'  AND table_name = 'bookmarks' AND table_schema = 'public'
                 * @todo This wont work for join tables with two primary keys
                 */
                if ($isAuto or ($result['name'] === 'id' and $data['type'] === 'integer')) {
                    $data['key'] = 'primary'; // Assume id is primary key
                    $data['type'] = 'primaryKey';
                }
                $schema[$result['name']] = $data;
            }
        }

        return $schema;
    }
}
