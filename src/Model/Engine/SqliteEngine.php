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
namespace Origin\Model\Engine;

use Exception;
use Origin\Model\Connection;

class SqliteEngine extends Connection
{
    protected $name = 'sqlite';
      
    /**
     * What to quote table and column aliases
     *
     * @var string
     */
    protected $quote = '"';

    /**
     * Returns the DSN string
     *
     * @param array $config
     * @return string
     */
    public function dsn(array $config): string
    {
        extract($config);
    
        if ($database) {
            return "sqlite:{$database}";
        }

        return  'sqlite::memory:';
    }

    /**
     * Gets a list of tables
     * @todo when this is refactored test each item individually.
     * @return array
     */
    public function tables(): array
    {
        $tables = [];
        $this->execute('SELECT name from sqlite_master WHERE type = "table"');
        $results = $this->fetchList();
        if ($results) {
            sort($results);
            $key = array_search('sqlite_sequence', $results);
            unset($results[$key]);
            $results = array_values($results);
        }

        return $results ?? $tables;
    }

    /**
    * Gets a list of databases
    *
    * @return array
    */
    public function databases(): array
    {
        return [];
    }

    /**
     * Creates and handles a DB transaction with the option to disable foreign key constraints.
     *
     * @example
     *
     * $connection->transaction(function ($connection) use ($statements) {
     *     $this->processStatements($connection,$statements);
     * });
     *
     * @param callable $callback
     * @param boolean $disbleForeignKeyConstraints
     * @return mixed
     */
    public function transaction(callable $callback, bool $disbleForeignKeyConstraints = false)
    {
        if ($disbleForeignKeyConstraints) {
            $this->disableForeignKeyConstraints();
        }
        
        $this->begin();
      
        try {
            $result = $callback($this);
        } catch (Exception $exception) {
            $this->rollback();
            if ($disbleForeignKeyConstraints) {
                $this->enableForeignKeyConstraints();
            }

            throw $exception;
        }

        if ($result === false) {
            $this->rollback();
        } else {
            $this->commit();
        }

        if ($disbleForeignKeyConstraints) {
            $this->enableForeignKeyConstraints();
        }

        return $result;
    }
}
