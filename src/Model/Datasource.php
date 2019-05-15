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
use Origin\Core\Logger;
use Origin\Model\Exception\DatasourceException;
use PDO;
use PDOException;
use Origin\Model\QueryBuilder;

abstract class Datasource
{
    /**
     * The datasource name e.g mysql or pgsql
     *
     * @var string
     */
    protected $name = null;
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


    /**
     * Holds the connection config
     *
     * @var array
     */
    protected $config = [];
    /**
     * What to escape table and column aliases
     *
     * @var string
     */
    protected $escape = '';

    /**
     * Holds the schema adapter
     *
     * @var \Origin\Model\Schema\BaseSchema
     */
    protected $adapter = null;

    public function __construct(array $config = []){
        $this->config = $config;
    }

    /**
     * connects to database.
     *
     * @param array $config
     */
    public function connect(array $config)
    {
        $config += ['engine'=>'mysql'];
       
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
            throw new DatasourceException($e->getMessage());
        }
    }

    /**
     * Checks if connected
     *
     * @return boolean
     */
    public function isConnected(){
        if($this->connection === null){
            return false;
        }
        
        return is_object($this->connection);
    }

    /**
     * Gets the db engine e.g. mysql or pgsql
     *
     * @return string
     */
    public function engine() : string
    {
        return $this->name;
    }


    public function database()
    {
        if(isset($this->config['database'])){
            return $this->config['database'];
        }
        return null;
    }

    /**
     * Gets the quote identifier for this datasource
     *
     * @return string
     */
    public function quoteIdentifier(){
        return $this->escape;
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
                $this->log[] = [
                'query' => $this->unprepare($sql, $params),
                'error' => !$result,
                'affected' => $this->lastAffected(),
                'time' => microtime(true) - $start,
              ];
            }

            // Fallback if dsiable PDO::ERRMODE_EXCEPTION flag
            if (!$result) {
                return false;
            }
        } catch (PDOException $e) {
            $logger = new Logger('Datasource');
            $logger->debug($this->unprepare($sql, $params));
            throw new DatasourceException($e->getMessage());
        }

        return true;
    }

    protected function unprepare($sql, $params)
    {
        foreach ($params as $needle => $replace) {
            if (is_string($replace)) {
                $replace = "'{$replace}'";
            }
            $sql = preg_replace("/\B:{$needle}/", $replace, $sql);
        }
        return $sql;
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
    /**
     * Disconnects the database
     *
     * @return void
     */
    public function disconnect()
    {
        if($this->connection){
            if($this->statement){
                $this->statement->closeCursor();
            }
        }
        $this->connection = null;
        $this->statement = null;
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
     * Fetches the next row from the database. Model method does not work with postgresql
     * cause meta for table does not return alias.
     *
     * @param string $type (num | assoc | model | object)
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
     * @param PDOStatement $statement
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
            $column = $statement->getColumnMeta($i); // could be bottle neck on
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
     * Takes a numerical set results and maps to model. Originally was
     * using getColumnMeta(), however the table result which is used to map
     * does not work on postgresql. This will only work if all fields are quoted.
     *
     * @param array $records numerically index
     * @param array fields
     * @return array
     */
    public function mapNumericResults(array $records,array $fields) : array
    {

        $count = count($fields);
        $index = $this->getColumnMetaData($fields);

        $results = [];
         foreach($records as $record){
            $array = [];
            for($i=0;$i<$count;$i++){
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
    private function getColumnMetaData(array $fields) : array
    {
        $index = [];
        $count = count($fields);
    
        /**
         * Build an index
         */
        for($i=0;$i<$count;$i++){
            $model = 0; // default value
            $field = $fields[$i];
            if(preg_match('/^[A-Za-z0-9_]+\.[a-z0-9_]+$/i',$field)){
                list($model,$field) = explode('.',$fields[$i]);
            }
            
            $position  = stripos($fields[$i],' AS ');
            if($position !== false){
                $field = substr($field,$position + 4); 
                if(strpos($field,'__') !== false){ 
                    list($model,$field) = explode('__',$field);
                }
            }
            $index[$i] = [
                'model'=>$model,
                'field'=>$field
            ];
        }
        return $index;
    }

    /**
     * Driver Specific
     */
    /**
     * Gets the DSN string
     *
     * @param array $config
     * @return string
     */
    public function dsn(array $config)
    {
    }

 
    /**
     * Returns the schema adapter
     *
     * @return \Origin\Model\Schema\BaseSchema
     */
    public function adapter(){
        if(!$this->adapter){
            $adapterClass = 'Origin\Model\Schema\\'. ucfirst($this->name) . 'Schema';
            $this->adapter = new $adapterClass($this->config['datasource']);
        }
      
        return $this->adapter;
    }


    public function schema(string $table)
    {
       return $this->adapter()->schema($table);
    }

    /**
     * Returns an array of tables
     *
     * @return array
     */
    public function tables() : array
    {
        return $this->adapter()->tables();
    }
    
    public function enableForeignKeyConstraints(){

    }


    public function disableForeignKeyConstraints(){

    }

    /**
     * Returns a MySQL string for creating a table
     *
     * @param string $table
     * @param array $data
     * @return string
     */
    public function createTable(string $table, array $data) : string
    {
        return $this->adapter()->createTable($table,$data);
    }
 
    public function select(string $table, array $options)
    {
        $builder = $this->queryBuilder($table, $options['alias']);
        $sql = $builder->selectStatement($options);// How to handle this elegently without having to do same work as selct
        return $this->execute($sql, $builder->getValues());
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
     *
     * @return bool true or false
     */
    public function update(string $table, array $data, array $conditions = [])
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
     *
     * @return bool true or false
     */
    public function delete(string $table, array $conditions = [])
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
    public function queryBuilder(string $table, $alias=null)
    {
        return new QueryBuilder($table, $alias,[
            'escape'=>$this->escape
            ]);
    }

    public function log()
    {
        return $this->log;
    }
}
