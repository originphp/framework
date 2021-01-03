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

declare(strict_types=1);

namespace Origin\Model;

use Origin\Core\Exception\Exception;
use Origin\Model\Schema\TableSchema;

class Schema
{

    /**
     * Returns the SQL for creating tables, indexes and foreign keys
     *
     * @param \Origin\Model\Connection $datasource
     * @return array
     */
    public function createSql(Connection $datasource): array
    {
        $out = $foreignKeys = [];

        $properties = get_object_vars($this);

        /**
         * Extract foreignKeys to process after tables have been created.
         * This is important for pgsql when creating a table that references another
         * table that has not been created yet since foreign keys checks cannot be disabled
         */
        if ($datasource->engine() === 'postgres') {
            foreach (array_keys($properties) as $table) {
                $foreignKeys[$table] = [];
                $data = $properties[$table];
                if (isset($data['constraints'])) {
                    foreach ($data['constraints'] as $name => $settings) {
                        if (isset($settings['type']) && $settings['type'] === 'foreign') {
                            $foreignKeys[$table][$name] = $settings;
                            unset($properties[$table]['constraints'][$name]);
                        }
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
         * @example ALTER TABLE "bookmarks" ADD CONSTRAINT "bookmarks_ibfk_1" FOREIGN KEY (user_id) REFERENCES "users" (id) DEFERRABLE INITIALLY IMMEDIATE
         */
        if ($datasource->engine() === 'postgres') {
            foreach (array_keys($properties) as $table) {
                foreach ($foreignKeys[$table] as $name => $settings) {
                    if (! isset($settings['column']) || ! isset($settings['references'][0]) || ! isset($settings['references'][1])) {
                        throw new Exception(sprintf('Invalid foreign key settings for %s on table %s ', $name, $table));
                    }
                    $sql = $datasource->adapter()->addForeignKey($table, $name, $settings['column'], $settings['references'][0], $settings['references'][1]);
                    $out = array_merge($out, $sql);
                }
            }
        }

        return array_filter($out);
    }

    /**
     * Returns the SQL for dropping tables
     *
     * @param \Origin\Model\Connection $datasource
     * @return array
     */
    public function dropSql(Connection $datasource): array
    {
        $out = [];
        $properties = get_object_vars($this);

        foreach (array_keys($properties) as $table) {
            $out[] = $datasource->adapter()->dropTableSql($table);
        }

        return $out;
    }

    /**
     * Gets the schema for a table
     *
     * @param string $table
     * @return array|null
     */
    public function schema(string $table): ?array
    {
        return $this->$table ?? null;
    }
}
