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

namespace Origin\TestSuite;

use Origin\Core\Resolver;
use Origin\Core\Inflector;
use Origin\Exception\Exception;
use Origin\Model\ConnectionManager;

/**
 * The main goal of the fixture class is to insert records for each test. However, sometimes, the schema
 * will be needed to be created on the fly, so this also handles this. Features in Fixture have now become
 * obsolete since introducing the desired default behavior which is to use existing tables in the test database
 */
class Fixture
{
   
    /**
     * The table name used by this fixture. It is
     * guessed using the class name
     *
     * @var string
     */
    public $table = null;

    /**
     * Records to insert
     *
     * @var array
     */
    public $records = [];

    /**
     * Drops and recreates tables between tests. Default behavior is always drop tables.
     * @var bool
     */
    public $dropTables = true;

    /**
     * This is an internal flag to only insert records, but does not create or drop tables.
     *
     * @var boolean
     */
    protected $insertOnly = false;

    /**
    * Use this to create a custom table, using the information retreived from Model:schema(). This
    * is more for internal testing
    *
    * Types include primaryKey,string,text,decimal,float, integer.
    *
    * Keys include type, limit or precision and scale, null, default, and key (either primary or nothing)
    *
    *  ['name' => [
    *     'type' => 'string',
    *     'limit' => 255,
    *     'null' => false,
    *  ],
    *  'amount' => [
    *     'type' => 'decimal',
    *     'precision' => 10,
    *     'scale' => 10,
    *     'null' => false
    *  ],
    *  'created' => 'datetime']
    * @var array
    */
    public $schema = [];

    /**
    * This will be deprecated in the future. The idea is just to work with the test datasource, loading
    * the schema. This just makes things simpler.
    *
    * You can import data using a model OR table key. Either model or table The options are :
    *
    *   - datasource: default is default.
    *   - model: model name to import. This will load the information from model including the table.
    *   - table: the table to import.
    *   - records: default:false
    *
    * @var array|null
    */
    public $import = null;

    /**
    * The datasource to use, this should be test
    *
    * @var string
    */
    public $datasource = 'test';

    public function __construct()
    {
        if ($this->table === null) {
            list($namespace, $class) = namespaceSplit(get_class($this));
            $name = substr($class, 0, -7);
            $this->table = Inflector::tableize($name);
        }

        $this->initialize();

        $this->insertOnly = (empty($this->schema) and empty($this->import));
    }

    /**
     * Gets the insertOnlyFlag
     *
     * @return bool
     */
    public function insertOnly() :bool
    {
        return $this->insertOnly;
    }

    /**
     * Use to create dynamic records.
     */
    public function initialize()
    {
    }
    
    /**
     * As of 1.25.0 - This will be deprecated in the future.
     *
     * Import schema. If model is found then use that datasource and table else
     * try to guess it incase of dynamic models
     *
     * @param string $model
     * @return bool|null
     */
    public function import()
    {
        if ($this->import === null) {
            return;
        }
       
        $defaults = ['datasource' => 'default','model' => null,'table' => null,'records' => false];
        $options = array_merge($defaults, $this->import);
     
        // Load information from model is specified
        if ($options['model']) {
            $className = Resolver::className($options['model'], 'Model');
            if ($className) {
                $model = new $className();
                $options['datasource'] = $model->datasource;
                $options['table'] = $model->table;
            } else {
                $options['table'] = Inflector::tableize($options['model']); // for dynamic models fall back
            }
        }
        // Table is not specified or could not find model
        if (empty($options['table'])) {
            throw new Exception('Undefined table');
        }

        $connection = ConnectionManager::get($options['datasource']);
        $schema = $connection->schema($options['table']);
        
        /**
         * Imports records
         */
        if ($options['records']) {
            $this->records = $this->loadRecords($options['datasource'], $options['table']);
        }
    
        $connection = ConnectionManager::get($this->datasource);
        $sql = $connection->adapter()->createTable($this->table, $schema);
  
        return $connection->execute($sql);
    }

    /**
     * Loads records from a datasource
     *
     * @param string $datasource
     * @param string $table
     * @return array
     */
    public function loadRecords(string $datasource, string $table) : array
    {
        $connection = ConnectionManager::get($datasource);
        $connection->execute('SELECT * FROM ' . $table);

        $records = $connection->fetchAll();

        return $records ?? [];
    }

    /**
     * Creates the table.
     *
     * @return bool true or false
     */
    public function create() : bool
    {
        if ($this->import) {
            return $this->import();
        }
        $connection = ConnectionManager::get($this->datasource);
        $sql = $connection->adapter()->createTable($this->table, $this->schema);
   
        return $connection->execute($sql);
    }

    /**
     * Inserts the records.
     *
     * @return void
     */
    public function insert() : void
    {
        $connection = ConnectionManager::get($this->datasource);
   
        foreach ($this->records as $record) {
            $connection->insert($this->table, $record);
        }
    }

    /**
     * Drops the table.
     *
     * @return bool true or false
     */
    public function drop() : bool
    {
        $connection = ConnectionManager::get($this->datasource);

        return $connection->dropTable($this->table);
    }

    /**
     * Truncates the table.
     *
     * @return bool true or false
     */
    public function truncate() : bool
    {
        $connection = ConnectionManager::get($this->datasource);

        return $connection->truncateTable($this->table);
    }
}
