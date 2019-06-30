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
use Origin\Exception\Exception;

class PostgreSQLEngine extends Datasource
{
    protected $name = 'pgsql';

    /**
    * What to escape table and column aliases
    *
    * @var string
    */
    protected $escape = '"';

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
            return "{$engine}:host={$host};dbname={$database};options='--client_encoding=UTF8'";
        }
        return "{$engine}:host={$host};options='--client_encoding=UTF8'";
    }
    
    /**
    * Enables Foreign Key Constraints
    *
    * @return void
    */
    public function enableForeignKeyConstraints() : void
    {
        $this->execute('SET CONSTRAINTS ALL IMMEDIATE');
    }

    /**
     * Disables Foreign Key Constraints
     *
     * @return void
     */
    public function disableForeignKeyConstraints() : void
    {
        $this->execute('SET CONSTRAINTS ALL DEFERRED');
    }

    /**
     * Gets a list of tables
     *
     * @return array
     */
    public function tables() : array
    {
        $sql = 'SELECT table_name as "table" FROM information_schema.tables WHERE table_schema=\'public\'';
       
        $out = [];
        if ($this->execute($sql)) {
            $list = $this->fetchList();
            if ($list) {
                $out = $list;
            }
        }
        sort($out); // why sort with db server
        return $out;
    }

    /**
     * Returns a list of databases
     *
     * @return array
     */
    public function databases() : array
    {
        $sql = 'SELECT datname FROM pg_database WHERE datistemplate = false;';
        $out = [];
        if ($this->execute($sql)) {
            $list = $this->fetchList();
            if ($list) {
                $out = $list;
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
        return $this->execute("DROP TABLE IF EXISTS {$table} CASCADE");
    }

    /**
     * Truncates a table
     *
     * @param string $table
     * @return boolean
     */
    public function truncateTable(string $table) : bool
    {
        return $this->execute("TRUNCATE TABLE {$table} CASCADE");
    }
}
