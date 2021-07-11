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

use PDO;
use Exception;
use PDOException;
use PDOStatement;
use Origin\Log\Log;
use Origin\Cache\Cache;
use Origin\Model\Schema\BaseSchema;
use Origin\Model\Exception\ConnectionException;
use Origin\Model\Exception\DatasourceException;

/**
 * This is the connection class.
 */
abstract class Connection
{
    /**
     * The datasource name e.g mysql or postgres
     *
     * @var string
     */
    protected $name;

    /**
     * Holds the connection to datasource.
     *
     * @var \PDO
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
    protected $virtualFieldSeperator = '__';

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

    /**
     * Holds the connection config
     *
     * @var array
     */
    protected $config = [];
    /**
     * What to quote table and column aliases
     *
     * @var string
     */
    protected $escape = '';

    /**
     * Holds the schema adapter
     *
     * @var \Origin\Model\Schema\BaseSchema|null;
     */
    protected $adapter = null;

    /**
     * If transaction has been started on this datasource
     *
     * @var bool
     */
    protected $transactionStarted = false;

    /**
     * @var string
     */
    protected $quote = '`';

    /**
     * internal mapping to PDO
     *
     * @param array $config
     */
    private $typeMap = [
        'assoc' => PDO::FETCH_ASSOC,
        'num' => PDO::FETCH_NUM,
        'model' => PDO::FETCH_NUM,
        'obj' => PDO::FETCH_OBJ
    ];

    /**
     * Constructor
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Returns the connection configuration
     *
     * @return array
     */
    public function config(): array
    {
        return $this->config;
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
        $this->begin();

        if ($disbleForeignKeyConstraints) {
            $this->disableForeignKeyConstraints();
        }

        try {
            $result = $callback($this);
        } catch (Exception $exception) {
            if ($disbleForeignKeyConstraints) {
                $this->enableForeignKeyConstraints();
            }

            $this->rollback();
            throw $exception;
        }

        if ($disbleForeignKeyConstraints) {
            $this->enableForeignKeyConstraints();
        }

        if ($result === false) {
            $this->rollback();
        } else {
            $this->commit();
        }

        return $result;
    }

    /**
     * connects to database.
     *
     * @param array $config
     */
    public function connect(array $config): void
    {
        $config += ['engine' => 'mysql'];

        $flags = [
            PDO::ATTR_PERSISTENT => false,
            PDO::ATTR_EMULATE_PREPARES => false, // use real prepared statements
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ];
        try {
            $this->connection = new PDO(
                $this->dsn($config),
                $config['username'],
                $config['password'],
                $flags
            );
        } catch (PDOException $e) {
            Log::alert($e->getMessage());
            throw new ConnectionException($e->getMessage());
        }
    }

    /**
     * Checks if connected
     *
     * @return boolean
     */
    public function isConnected(): bool
    {
        return $this->connection !== null;
    }

    /**
     * Gets the db engine e.g. mysql or postgres
     *
     * @return string
     */
    public function engine(): string
    {
        return $this->name;
    }

    /**
     * Gets the database that its connected to
     *
     * @return string|null
     */
    public function database(): ?string
    {
        return $this->config['database'] ?? null;
    }

    /**
     * Executes a sql query.
     *
     * @param string $sql SQL statement
     * @param array $params ['name'=>'John'] or ['p1'=>'John']
     * @return bool
     */
    public function execute(string $sql, array $params = []): bool
    {
        try {
            $start = microtime(true);
            $this->statement = $query = $this->connection->prepare($sql);

            $result = $query->execute($params);
            if (debugEnabled()) {
                $this->log[] = [
                    'query' => $this->unprepare($sql, $params),
                    'error' => ! $result,
                    'affected' => $this->lastAffected(),
                    'time' => microtime(true) - $start,
                ];

                /**
                 * Limit to 200 queries to prevent memory issues
                 */
                if (count($this->log) > 200) {
                    array_shift($this->log);
                }
            }

            // Fallback if disabled PDO::ERRMODE_EXCEPTION flag
            if (! $result) {
                return false;
            }
        } catch (PDOException $e) {
            Log::debug($this->unprepare($sql, $params));
            /**
             * Important: rollback transcation if one was started.
             */
            $this->rollback();

            throw new DatasourceException($e->getMessage());
        }

        return true;
    }

    /**
     * Inperpolates SQL params into a SQL statement for debugging purposes.
     *
     * @param string $sql
     * @param array $params
     * @return string
     */
    protected function unprepare(string $sql, array  $params): string
    {
        $data = [];

        foreach ($params as $needle => $replace) {
            if (is_string($replace)) {
                $replace = "'{$replace}'";
            }
            if ($replace === null) {
                $replace = 'NULL';
            }
            $data[':' . $needle] = $replace;
        }

        return strtr($sql, $data);
    }

    /**
     * Check result object is part of PDOStatement.
     *
     * @internal Sqlite returning 0 for row count when running pragma.
     *
     * @return bool
     */
    public function hasResults(): bool
    {
        return is_a($this->statement, 'PDOStatement') && $this->statement->rowCount() > 0;
    }

    /**
     * Initiates a transaction.
     *
     * @return bool returns TRUE on success or FALSE on failure
     */
    public function begin(): bool
    {
        if ($this->transactionStarted) {
            return false;
        }

        if ($this->connection->inTransaction()) {
            return false;
        }
        
        $this->connection->beginTransaction();

        return $this->transactionStarted = true;
    }

    /**
     * Commits a transaction.
     *
     * @return bool returns TRUE on success or FALSE on failure
     */
    public function commit(): bool
    {
        if (! $this->transactionStarted) {
            return false;
        }
        $this->transactionStarted = false;

        if (! $this->connection->inTransaction()) {
            return false;
        }
       
        return $this->connection->commit();
    }

    /**
     * Rolls back the current transaction (if a transcation was started)
     *
     * @return bool returns TRUE on success or FALSE on failure
     */
    public function rollback(): bool
    {
        if (! $this->transactionStarted) {
            return false;
        }
        
        $this->transactionStarted = false;

        if (! $this->connection->inTransaction()) {
            return false;
        }

        return $this->connection->rollBack();
    }

    /**
     * Returns the ID of the last inserted row or sequence value
     *
     * @return string
     */
    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }

    /**
     * Return last affected
     *
     * @return integer
     */
    public function lastAffected(): int
    {
        if ($this->statement === null) {
            return 0;
        }

        return $this->statement->rowCount();
    }
    /**
     * Disconnects the database
     *
     * @return void
     */
    public function disconnect(): void
    {
        /*
        if ($this->connection) {
            if ($this->statement) {
                $this->statement->closeCursor();
            }
        }*/
        $this->connection = null;
        $this->statement = null;
    }

    /**
     * Fetchs a single record.
     *
     * @param string $type (num,assoc,model,object)
     * @return mixed record
     */
    public function fetch(string $type = 'assoc')
    {
        $fetchType = $this->typeMap[$type] ?? 'assoc';

        $result = $this->statement->fetch($fetchType);
        if ($result === false) {
            $this->statement->closeCursor();

            return null;
        }

        if ($type === 'model') {
            $this->mapColumns();

            return $this->toModel($result, $this->columnMap);
        }

        return $result;
    }

    /**
     * Returns a list
     *
     * @return array|null
     */
    public function fetchList(): ?array
    {
        $results = $this->statement->fetchAll(PDO::FETCH_NUM);
        if ($results === false) {
            $this->statement->closeCursor();

            return null;
        }

        return ! empty($results) ?  $this->toList($results) : null;
    }

    /**
     * Fetches multiple records.
     *
     * @param string $type (num,assoc,model,object)
     * @return array|null
     */
    public function fetchAll(string $type = 'assoc'): ?array
    {
        $fetchType = $this->typeMap[$type] ?? 'assoc';

        if ($this->statement && $type === 'model') {
            $this->mapColumns();
        }

        $results = $this->statement->fetchAll($fetchType);
        if ($results === false) {
            $this->statement->closeCursor();

            return null;
        }

        if ($type !== 'model') {
            return $results;
        }

        $rows = [];

        foreach ($results as $row) {
            $rows[] = $this->toModel($row, $this->columnMap);
        }

        return $rows;
    }

    /**
     * Converts rows from fetch all to a list
     * 3 different list types ['a','b','c'] or ['a'=>'b'] or ['c'=>['a'=>'b']] depending upon how many columns are selected. If more than 3 columns selected it returns ['a'=>'b'].
     *
     * @param array $rows fetchAll rows
     * @return array
     */
    protected function toList(array $rows): array
    {
        $result = [];

        $columnCount = count($rows[0]);
        foreach ($rows as $row) {
            if ($columnCount == 1) {
                $result[] = $row[0];
                continue;
            }

            if ($columnCount == 3) {
                if (! isset($result[$row[2]])) {
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
     * @return array
     */
    protected function toModel(array $row, array $map): array
    {
        $result = [];
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
     * @internal getColumnMeta does not work with PostgreSql, table returns table name instead of alias
     *
     * @param PDOStatement $statement
     * @return void
     */
    public function mapColumns(PDOStatement $statement = null): void
    {
        $this->columnMap = [];
        if ($statement == null) {
            $statement = $this->statement;
        }

        $numberOfFields = $statement->columnCount();

        for ($i = 0; $i < $numberOfFields; ++$i) {
            $column = $statement->getColumnMeta($i); // could be bottle neck on
            if (empty($column['table']) || $this->isVirtualField($column['name'])) {
                $this->columnMap[$i] = [0, $column['name']];
            } else {
                $this->columnMap[$i] = [$column['table'], $column['name']];
            }
        }
    }

    /**
     * Checks if a column is a virtual field.
     *
     * @param string $column
     * @return bool
     */
    public function isVirtualField(string $column): bool
    {
        return strpos($column, $this->virtualFieldSeperator) != false;
    }

    /**
     * Takes a numerical set results and maps to model. Originally was
     * using getColumnMeta(), however the table result which is used to map
     * does not work on postgresql. This will only work if all fields are quoted.
     *
     * @param array $records numerically index
     * @param array $fields
     * @return array
     */
    public function mapNumericResults(array $records, array $fields): array
    {
        $count = count($fields);
        $index = $this->getColumnMetaData($fields);

        $results = [];
        foreach ($records as $record) {
            $array = [];
            for ($i = 0; $i < $count; $i++) {
                $model = $index[$i]['model'];
                $field = $index[$i]['field'];
                $array[$model][$field] = $record[$i];
            }
            $results[] = $array;
        }

        unset($records);

        return $results;
    }

    /**
     * Returns the column meta data form fields
     *
     * @param array $fields
     * @return array
     */
    private function getColumnMetaData(array $fields): array
    {
        $index = [];
        $count = count($fields);

        /**
         * Build an index
         */
        for ($i = 0; $i < $count; $i++) {
            $model = 0; // default value
            $field = $fields[$i];
            if (preg_match('/^[A-Za-z0-9_]+\.[a-z0-9_]+$/i', $field)) {
                list($model, $field) = explode('.', $fields[$i]);
            }

            $position = stripos($fields[$i], ' AS ');
            if ($position !== false) {
                $field = substr($field, $position + 4);
                if (strpos($field, '__') !== false) {
                    list($model, $field) = explode('__', $field);
                }
            }
            $index[$i] = [
                'model' => $model,
                'field' => $field,
            ];
        }

        return $index;
    }

    /**
     * Returns the schema adapter
     *
     * @return \Origin\Model\Schema\BaseSchema
     */
    public function adapter(): BaseSchema
    {
        if ($this->adapter === null) {
            $adapterClass = __NAMESPACE__ . '\Schema\\' . ucfirst($this->name) . 'Schema';
            $this->adapter = new $adapterClass($this->config['connection']);
        }

        return $this->adapter;
    }

    /**
     * Describes the table
     *
     * @param string $table
     * @return array
     */
    public function describe(string $table): array
    {
        $key = $this->config['name'] . '_' . $table;
        $schema = Cache::read($key, ['config' => 'origin']);

        if (! $schema) {
            $schema = $this->adapter()->describe($table);
            if (! defined('PHPUNIT')) {
                Cache::write($key, $schema, ['config' => 'origin']);
            }
        }

        return $schema;
    }

    /**
     * Gets the DSN string
     *
     * @param array $config
     * @return string
     */
    abstract public function dsn(array $config);

    /**
     * Gets a list of database
     *
     * @return array
     */
    abstract public function databases();
    /**
     * Gets a list of tables
     *
     * @return array
     */
    abstract public function tables(): array;

    /**
     * Enables Foreign Key Constraints
     *
     * @return void
     */
    public function enableForeignKeyConstraints(): void
    {
        $this->execute($this->adapter()->enableForeignKeySql());
        $this->statement->closeCursor();
    }

    /**
     * Disables Foreign Key Constraints
     *
     * @return void
     */
    public function disableForeignKeyConstraints(): void
    {
        $this->execute($this->adapter()->disableForeignKeySql());
        $this->statement->closeCursor();
    }

    /**
     * Executes a select statement
     *
     * @param string $table
     * @param array $options
     * @return bool
     */
    public function select(string $table, array $options): bool
    {
        $builder = $this->queryBuilder($table, $options['alias']);
        $sql = $builder->selectStatement($options); // How to handle this elegently without having to do same work as selct

        return $this->execute($sql, $builder->getValues());
    }

    /**
     * Inserts a row into the database.
     *
     * @param string $table
     * @param array  $data
     * @return bool
     */
    public function insert(string $table, array $data): bool
    {
        $builder = $this->queryBuilder($table);
        $sql = $builder->insert($data)
            ->write();

        return $this->execute($sql, $builder->getValues());
    }

    /**
     * Updates a table.
     *
     * @param string $table
     * @param array  $data
     * @param array  $conditions
     * @return bool
     */
    public function update(string $table, array $data, array $conditions = []): bool
    {
        $builder = $this->queryBuilder($table);
        $sql = $builder->update($data)
            ->where($conditions)
            ->write();

        return $this->execute($sql, $builder->getValues());
    }

    /**
     * Deletes from a table.
     *
     * @param string $table
     * @param array  $conditions
     * @return bool
     */
    public function delete(string $table, array $conditions = []): bool
    {
        $builder = $this->queryBuilder($table);
        $sql = $builder->delete($conditions)
            ->write();

        return $this->execute($sql, $builder->getValues());
    }

    /**
     * Returns a query builder object.
     *
     * @param string $table [description]
     * @return \Origin\Model\QueryBuilder QueryBuilder
     */
    public function queryBuilder(string $table, $alias = null): QueryBuilder
    {
        return new QueryBuilder($table, $alias, [
            'escape' => $this->quote,
        ]);
    }

    /**
     * Returns the log
     *
     * @return array
     */
    public function log(): array
    {
        return $this->log;
    }

    /*
     TODO:
     public function getSQLStatement(): ? string
     {
         return $this->sqlStatment;
     }
     */
}
