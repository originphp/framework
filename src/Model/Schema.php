<?php
namespace Origin\Model;

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
        $tables = array_keys($properties);
        foreach ($tables as $table) {
            $columns = $properties[$table]['columns'] ?? [];
            $schema = new TableSchema($table, $columns, $properties[$table]);
            $out = array_merge($out, $schema->toSql($datasource));
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
