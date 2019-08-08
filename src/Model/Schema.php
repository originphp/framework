<?php
namespace Origin\Model;

use Origin\Exception\Exception;
use Origin\Model\Schema\TableSchema;

class Schema
{
    /**
     * Returns the SQL for creating tables, indexes and foreign keys
     *
     * @param Datasource $datasource
     * @return array
     */
    public function createSql(Datasource $datasource) : array
    {
        $out = [];

        $properties = get_object_vars($this);

        /**
         * Extract foreignKeys to process after tables have been created.
         * This is important for pgsql when creating a table that references another
         * table that has not been created yet since foreign keys checks cannot be disabled
         */
        $foreignKeys = [];
        foreach (array_keys($properties) as $table) {
            $foreignKeys[$table] = [];
            $data = $properties[$table];
            if (isset($data['constraints'])) {
                foreach ($data['constraints'] as $name => $settings) {
                    if (isset($settings['type']) and $settings['type'] === 'foreign') {
                        $foreignKeys[$table][$name] = $settings;
                        unset($properties[$table]['constraints'][$name]);
                    }
                }
            }
        }
        
        /**
         * Create the create table statements
         */
        foreach (array_keys($properties) as $table) {
            $columns = $properties[$table]['columns'] ?? [];
            $schema = new TableSchema($table, $columns, $properties[$table]);
            $out = array_merge($out, $schema->toSql($datasource));
        }

        /**
         * Add all foreign keys statements
         */
        foreach (array_keys($properties) as $table) {
            foreach ($foreignKeys[$table] as $name => $settings) {
                if (! isset($settings['column']) or ! isset($settings['references'][0]) or ! isset($settings['references'][1])) {
                    throw new Exception(sprintf('Invalid foreign key settings for %s on table %s ', $name, $table));
                }
                $out[] = $datasource->adapter()->addForeignKey($table, $name, $settings['column'], $settings['references'][0], $settings['references'][1]);
            }
        }

        return $out;
    }

    /**
     * Returns the SQL for dropping tables
     *
     * @param Datasource $datasource
     * @return array
     */
    public function dropSql(Datasource $datasource) : array
    {
        $out = [];
        $properties = get_object_vars($this);
        $tables = array_keys($properties);
        foreach ($tables as $table) {
            $out[] = $datasource->adapter()->dropTable($table);
        }

        return $out;
    }
}
