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
namespace Origin\Model\Engine;

use Origin\Model\Datasource;

class MySQLEngine extends Datasource
{
    protected $name = 'mysql';
      
    /**
     * What to quote table and column aliases
     *
     * @var string
     */
    protected $quote = '`';

    /**
     * Returns the DSN string
     *
     * @param array $config
     * @return string
     */
    public function dsn(array $config) : string
    {
        extract($config);
        if ($database) {
            return "{$engine}:host={$host};dbname={$database};charset=utf8mb4";
        }

        return  "{$engine}:host={$host};charset=utf8mb4";
    }

    /**
     * Gets a list of tables
     * @todo when this is refactored test each item individually.
     * @return array
     */
    public function tables() : array
    {
        $tables = [];
        $this->execute('SHOW TABLES;');
        $results = $this->fetchAll();
        if ($results) {
            foreach ($results as $value) {
                $tables[] = current($value);
            }
        }

        return $tables;
    }

    /**
    * Gets a list of tables
    *
    * @return array
    */
    public function databases() : array
    {
        $out = [];
        $this->execute('SHOW DATABASES;');
        $results = $this->fetchAll();
        if ($results) {
            foreach ($results as $value) {
                $out[] = current($value);
            }
        }

        return $out;
    }

    /**
     * Drops a table
     *
     * @param string $table
     * @return bool
     */
    public function dropTable(string $table) : bool
    {
        return $this->execute("DROP TABLE IF EXISTS {$table}");
    }

    /**
     * Truncates a table
     *
     * @param string $table
     * @return bool
     */
    public function truncateTable(string $table) : bool
    {
        return $this->execute("TRUNCATE TABLE {$table}");
    }
}
