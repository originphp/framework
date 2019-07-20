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

use Origin\Model\ConnectionManager;

class PgsqlSchema extends BaseSchema
{
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
        WHERE  table_catalog = \'' . $this->connection()->database() . '\' AND  table_name = \'' . $table . '\' AND table_schema = \'public\'  ORDER BY position';

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

                /**
                 * Detect Primary Key
                 * @see SELECT * from information_schema.columns WHERE table_catalog = 'origin'  AND table_name = 'bookmarks' AND table_schema = 'public'
                 * @todo This wont work for join tables with two primary keys
                 */
                if ($result['name'] === 'id' and $data['type'] === 'integer') {
                    $data['key'] = 'primary'; // Assume id is primary key
                    $data['type'] = 'primaryKey';
                }
                $schema[$result['name']] = $data;
            }
        }

        return $schema;
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
        $sql = "SELECT i.relname AS name, a.attname AS column, ix.indisunique AS unique FROM pg_class t, pg_class i, pg_index ix, pg_attribute a WHERE t.oid = ix.indrelid AND i.oid = ix.indexrelid AND a.attrelid = t.oid AND a.attnum = ANY (ix.indkey) AND t.relkind = 'r' AND t.relname = '{$table}' ORDER BY t.relname, i.relname";
        $results = $this->fetchAll($sql);
        foreach ($results as $result) {
            $result['unique'] = strtolower($result['unique']) === 'true' ? true : false;
        }

        return $results;
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
        return  "ALTER TABLE {$from} RENAME TO {$to}";
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

        // Set the data type
        $sql = "ALTER TABLE {$table} ALTER COLUMN {$name} SET DATA TYPE {$type}";
        $options += ['default' => null, 'null' => null];

        if (! empty($options['default']) and $options['null'] === false) {
            $sql .= ", ALTER COLUMN {$name} SET DEFAULT " . $this->columnValue($options['default']) .", ALTER COLUMN {$name} SET NOT NULL";
        } elseif (isset($options['default'])) {
            $sql .= ", ALTER COLUMN {$name} SET DEFAULT " . $this->columnValue($options['default']);
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
        return "ALTER TABLE {$table} RENAME COLUMN {$from} TO {$to}";
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
        return "DROP INDEX {$name}";
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
        return "ALTER INDEX {$oldName} RENAME TO {$newName}";
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

        $sql = 'SELECT tc.table_name, kcu.column_name as column_name,tc.constraint_name AS constraint_name, ccu.table_name AS referenced_table_name, ccu.column_name AS referenced_column_name FROM information_schema.table_constraints AS tc JOIN information_schema.key_column_usage AS kcu ON tc.constraint_name = kcu.constraint_name JOIN information_schema.constraint_column_usage AS ccu ON ccu.constraint_name = tc.constraint_name WHERE  tc.table_catalog = \'' . $this->connection()->database() . '\' AND  tc.table_name = \'' . $table . '\' AND tc.table_schema = \'public\' AND tc.constraint_type = \'FOREIGN KEY\'';

        $results = $this->fetchAll($sql);

        return $results;
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
        return "ALTER TABLE {$fromTable} DROP CONSTRAINT {$constraint}";
    }

    /**
     * No easy way to do with pgsql. Pref is to use pgdump command
     *
     * @param string $table
     * @return string
     */
    public function showCreateTable(string $table): string
    {
        $schema = $this->schema($table);

        return $this->createTable($table, $schema);
    }

    /**
     * Prepares a column value
     *
     * @param mixed $value
     * @return mixed
     */
    public function columnValue($value)
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
        if (is_int($value)) {
            return $value;
        }

        return "'{$value}'";
    }
}
