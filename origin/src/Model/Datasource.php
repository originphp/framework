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

namespace Origin\Model;

use Origin\Core\Configure;
use Origin\Core\Log;
use Origin\Model\Exception\DatasourceException;
use PDO;
use PDOException;

class Datasource
{
    /**
     * Holds the connection to datasource.
     *
     * @var resource
     */
    protected $connection = null;

    /**
     * PDO statement returned from executing
     */
    protected $statement = null;

    /**
     * @example Virtual fields are CONCAT(Lead.first_name, " ", Lead.last_name) AS Lead__name
     *
     * @var string
     */
    public $virtualFieldSeperator = '__';

    /**
     * Transaction Log.
     *
     * @var array
     */
    protected $log = [];

    /**
     * Holds the map for the current fetch.
     *
     * @var array
     */
    private $columnMap = [];

    private $queryBuilder = null;

    /**
     * connects to database.
     *
     * @param array $config
     */
    public function connect(array $config)
    {
        extract($config);
        $dsn = "mysql:host={$host};dbname={$database};charset=utf8mb4";

        $flags = array(
          PDO::ATTR_PERSISTENT => false,
          PDO::ATTR_EMULATE_PREPARES => false, // use real prepared statements
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        );

        try {
            $this->connection = new PDO($dsn, $username, $password, $flags);
        } catch (PDOException $e) {
            throw new DatasourceException($e->getMessage());
        }
    }

    /**
     * Executes a sql query.
     *
     * @param string $sql    statement
     * @param array  $params array('name'=>'John') or array('p1'=>'John')
     *
     * @return bool result
     */
    public function execute(string $sql, array $params = [])
    {
        try {
            $start = microtime(true);

            $this->statement = $query = $this->connection->prepare($sql);

            $result = $query->execute($params);

            if (Configure::read('debug')) {
                $sql = $sql;
                foreach ($params as $needle => $replace) {
                    if (is_string($replace)) {
                        $replace = "'{$replace}'";
                    }
                    $sql = str_replace(':'.$needle, $replace, $sql);
                }
                $this->log[] = [
                'query' => $sql,
                'error' => !$result,
                'affected' => $this->lastAffected(),
                'time' => microtime(true) - $start,
              ];
            }

            // Fallback - PDO::ERRMODE_EXCEPTION flag moots this
            if (!$result) {
                return false;
            }
        } catch (PDOException $e) {
            if (isset($query->queryString)) {
                $sql = $query->queryString;
            }
            Log::write('sql-error', $sql);
            throw new DatasourceException($e->getMessage());
        }

        return true;
    }

    /**
     * Check result object is part of PDOStatement.
     *
     * @return bool
     */
    public function hasResults()
    {
        return is_a($this->statement, 'PDOStatement') and $this->statement->rowCount() > 0;
    }

    /**
     * Initiates a transaction.
     *
     * @return bool returns TRUE on success or FALSE on failure
     */
    public function begin()
    {
        return $this->connection->beginTransaction();
    }

    /**
     * Commits a transaction.
     *
     * @return bool returns TRUE on success or FALSE on failure
     */
    public function commit()
    {
        $this->connection->commit();
    }

    /**
     * Rolls back the current transaction.
     *
     * @return bool returns TRUE on success or FALSE on failure
     */
    public function rollback()
    {
        $this->connection->rollBack();
    }

    public function lastInsertId()
    {
        return $this->connection->lastInsertId();
    }

    public function lastAffected()
    {
        if ($this->hasResults()) {
            return $this->statement->rowCount();
        }

        return 0;
    }

    public function disconnect()
    {
        $this->connection = null;
    }

    /**
     * Fetchs a single record.
     *
     * @param string $type (num,assoc,model,object)
     *
     * @return mixed record
     */
    public function fetch(string $type = 'assoc')
    {
        if ($this->hasResults()) {
            if ($type == 'model') {
                $this->mapColumns();
            }

            return $this->fetchResult($type);
        }

        return null;
    }

    public function fetchList()
    {
        if ($this->hasResults()) {
            return $this->toList($this->statement->fetchAll(PDO::FETCH_NUM));
        }

        return null;
    }

    /**
     * Fetches multiple records.
     *
     * @param string $type (num,assoc,model,object)
     *
     * @return mixed record
     */
    public function fetchAll(string $type = 'assoc')
    {
        if ($this->hasResults()) {
            $rows = [];
       
            if ($type == 'model') {
                $this->mapColumns();
            }
       
            while ($row = $this->fetchResult($type)) {
                $rows[] = $row;
            }

            return $rows;
        }
    

        return null;
    }

    /**
     * Fetches the next row from the database.
     *
     * @param string $type (num | assoc | model | object)
     *
     * @return array row
     */
    protected function fetchResult(string $type = 'assoc')
    {
        $fetchType = PDO::FETCH_ASSOC;
        if ($type === 'num' or $type == 'model') {
            $fetchType = PDO::FETCH_NUM;
        } elseif ($type === 'obj') {
            $fetchType = PDO::FETCH_OBJ;
        }

        if ($row = $this->statement->fetch($fetchType)) {
            if ($type == 'model') {
                $row = $this->toModel($row, $this->columnMap);
            }

            return $row;
        }
       
        $this->statement->closeCursor();

        return false;
    }

    /**
     * Converts rows from fetch all to a list
     * 3 different list types ['a','b','c'] or ['a'=>'b'] or ['c'=>['a'=>'b']] depending upon how many columns are selected. If more than 3 columns selected it returns ['a'=>'b'].
     *
     * @param array $rows fetchAll rows
     *
     * @return array list
     */
    protected function toList(array $rows)
    {
        $columnCount = count($rows[0]);
        foreach ($rows as $row) {
            if ($columnCount == 1) {
                $result[] = $row[0];
                continue;
            }

            if ($columnCount == 3) {
                if (!isset($result[$row[2]])) {
                    $result[$row[2]] = [];
                }
                $result[$row[2]][$row[0]] = $row[1];
                continue;
            }

            $result[$row[0]] = $row[1];
            continue;
        }

        return $result;
    }

    /**
     * Converts a row Assoc with Alais.
     *
     * @param array $row
     * @param array $map array(model,column)
     *
     * @return result $row
     */
    protected function toModel(array $row, array $map)
    {
        $result = array();
        foreach ($map as $index => $meta) {
            list($table, $column) = $meta;

            // Assume Article__ref is for Article model
            if ($this->isVirtualField($column)) {
                list($table, $column) = explode($this->virtualFieldSeperator, $column);
            }
            $result[$table][$column] = $row[$index];
        }

        return $result;
    }

    /**
     * Builds a map so that an assoc array can be setup.
     *
     * @param PDOStatement $statement
     *
     * @return array $result
     */
    public function mapColumns(PDOStatement $statement = null)
    {
        $this->columnMap = [];
        if ($statement == null) {
            $statement = $this->statement;
        }
        $numberOfFields = $statement->columnCount();
        for ($i = 0; $i < $numberOfFields; ++$i) {
            $column = $statement->getColumnMeta($i);
            if (empty($column['table']) or $this->isVirtualField($column['name'])) {
                $this->columnMap[$i] = array(0, $column['name']);
            } else {
                $this->columnMap[$i] = array($column['table'], $column['name']);
            }
        }
    }

    /**
     * Checks if a column is a virtual field.
     *
     * @param string $column
     *
     * @return bool
     */
    public function isVirtualField(string $column)
    {
        return strpos($column, $this->virtualFieldSeperator) != false;
    }

    /**
     * Returns the schema for a table.
     *
     * @param string $table
     *
     * @return array (field = [type,length,default,null])
     */
    public function schema(string $table)
    {
        $schema = array();
        if ($this->execute("SHOW FULL COLUMNS FROM {$table};")) {
            $result = $this->fetchAll();

            foreach ($result as $column) {
                $length = null;
                $type = str_replace(')', '', $column['Type']);
                if (strpos($type, '(') !== false) {
                    list($type, $length) = explode('(', $type);
                }
                $schema[$column['Field']] = array(
                  'type' => $type,
                  'length' => $length,
                  'default' => $column['Default'],
                  'null' => ($column['Null'] === 'YES' ? true : false),
                  'key' => ($column['Key'] === 'PRI' ? 'primary' : null),
                );
            }
        }

        return $schema;
    }

    /**
     * Gets a list of tables for the datasource
     *
     * @return void
     */
    public function tables()
    {
        $tables = [];
        if ($this->execute('SHOW TABLES;')) {
            $result = $this->fetchAll();
            foreach ($result as $value) {
                $tables[] = current($value);
            }
        }
        return $tables;
    }

    /**
     * Inserts a row into the database.
     *
     * @param string $table
     * @param array  $data
     *
     * @return bool true or false
     */
    public function insert(string $table, array $data)
    {
        $Query = $this->queryBuilder($table);
        $sql = $Query->insert($data)
                      ->write();

        return $this->execute($sql, $Query->getValues());
    }

    /**
     * Updates a table.
     *
     * @param string $table
     * @param array  $data
     * @param array  $conditions
     *
     * @return bool true or false
     */
    public function update(string $table, array $data, array $conditions = [])
    {
        $Query = $this->queryBuilder($table);
        $sql = $Query->update($data)
                    ->where($conditions)
                    ->write();

        return $this->execute($sql, $Query->getValues());
    }

    /**
     * Deletes from a table.
     *
     * @param string $table
     * @param array  $conditions
     *
     * @return bool true or false
     */
    public function delete(string $table, array $conditions = [])
    {
        $Query = $this->queryBuilder($table);
        $sql = $Query->delete($conditions)
                    ->write();

        return $this->execute($sql, $Query->getValues());
    }

    /**
     * Returns a query builder object.
     *
     * @todo m
     *
     * @param string $table [description]
     *
     * @return object QueryBuilder
     */
    public function queryBuilder(string $table)
    {
        return new QueryBuilder($table);
    }

    public function log()
    {
        return $this->log;
    }
}
