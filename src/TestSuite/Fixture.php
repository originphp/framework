<?php
declare(strict_types = 1);
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
use Origin\Utility\Inflector;
use Origin\Exception\Exception;
use Origin\Model\ConnectionManager;
use Origin\Model\Schema\TableSchema;

/**
 * The main goal of the fixture class is to insert records for each test. However, sometimes, the schema
 * will be needed to be created on the fly, so this also handles this. Features in Fixture have now become
 * obsolete since introducing the desired default behavior which is to use existing tables in the test database
 */
class Fixture
{
    /**
     * The table name used by this fixture. It is
     * guessed using the class name. ArticleFixture treats table as articles
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
    * Use this to create a custom table, using the information retreived from Model:describe('table');
    * example:
    * public $schema = [
    *        'columns' => [
    *            'id' => ['type' => 'integer','autoIncrement' => true],
    *            'author_id' => ['type' => 'integer'],
    *            'description' => 'text',
    *            'created' => 'datetime',
    *            'modified' => 'datetime',
    *        ],
    *        'constraints' => [
    *            'primary' => [
     *                 'type' => 'primary','column' => 'id'
     *            ],
    *        ],
    *        'indexes' => [
    *             'title_slug_idx'=>[
    *                   'type'=>'index','column'=>['title','slug']]
    *          ]
    *    ];
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
    * The datasource config to use - it should be test
    * @deprecated This is only used by legacy functions
    * @var string
    */
    public $datasource = 'test';

    public function __construct()
    {
        if ($this->table === null) {
            list($namespace, $class) = namespaceSplit(get_class($this));
            $name = substr($class, 0, -7);
            $this->table = Inflector::tableName($name);
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
        if ($this->isLegacy()) {
            return $this->legacyCreate();
        }
        
        $connection = ConnectionManager::get('test');
        $table = new TableSchema($this->table, $this->schema['columns'], $this->schema);

        foreach ($table->toSql($connection) as $statement) {
            $connection->execute($statement);
        }
       
        return true; // Backwards compatability
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
        $sql = $connection->adapter()->dropTableSql($this->table, ['ifExists' => true]);

        return $connection->execute($sql);
    }

    /**
     * Truncates the table.
     *
     * @return bool true or false
     */
    public function truncate() : bool
    {
        $connection = ConnectionManager::get($this->datasource);
        $sql = $connection->adapter()->truncateTableSql($this->table);

        return $connection->execute($sql);
    }

    # Functions for deprecated features

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
                $options['table'] = Inflector::tableName($options['model']); // for dynamic models fall back
            }
        }
        // Table is not specified or could not find model
        if (empty($options['table'])) {
            throw new Exception('Undefined table');
        }

        $connection = ConnectionManager::get($options['datasource']);
        $schema = $connection->schema($options['table']); // being deprecated
        
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
    * Checks if deprecated features are being used.
    *
    * @return boolean
    */
    protected function isLegacy() : bool
    {
        return ($this->import or ! isset($this->schema['columns']));
    }

    /**
     * Legacy handler
     *
     * @return boolean
     */
    protected function legacyCreate() : bool
    {
        if ($this->import) {
            return $this->import();
        }
        $connection = ConnectionManager::get($this->datasource);
        $sql = $connection->adapter()->createTable($this->table, $this->schema);

        return $connection->execute($sql);

        return true;
    }
}
