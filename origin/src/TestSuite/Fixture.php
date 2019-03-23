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

use Origin\Core\Inflector;
use Origin\Model\ConnectionManager;
use Origin\Model\Schema;
use Origin\Model\ModelRegistry;
use Origin\Core\Resolver;
use Origin\Exception\Exception;

class Fixture
{
    public $datasource = 'test';

    public $table = null;

    public $fields = [];

    public $records = [];

    /**
     * You can import data using a model or table key
     *
     * @var array|null
     */
    public $import = null;

    /**
     * Drops and recreates tables between tests.
     *
     * @var bool
     */
    public $dropTables = false;

    public function __construct()
    {
        if ($this->table === null) {
            list($namespace, $class) = namespaceSplit(get_class($this));
            $name =  substr($class, 0, -7);
            $this->table = Inflector::tableize($name);
        }

        $this->initialize();
    }

    /**
     * Use to create dynamic records.
     */
    public function initialize()
    {
    }
    
    /**
     * Import schema. If model is found then use that datasource and table else
     * try to guess it incase of dynamic models
     *
     * @param string $model
     * @return void
     */
    public function import()
    {
        if ($this->import === null) {
            return;
        }
       
        $defaults = ['datasource'=>'default','model'=>null,'table'=>null];

        $options = array_merge($defaults, $this->import);

        // Load information from model is specificied
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
        // Table is not specificied or could not find model
        if (empty($options['table'])) {
            throw new Exception('Undefined table');
        }

        $connection = ConnectionManager::get($options['datasource']);
        $schema = $connection->schema($options['table']);
      
        $connection = ConnectionManager::get($this->datasource);
        $sql = $connection->createTable($this->table, $schema);
        return $connection->execute($sql);
    }

    /**
     * Creates the table.
     *
     * @return bool true or false
     */
    public function create()
    {
        if ($this->import) {
            return $this->import();
        }
        $connection = ConnectionManager::get($this->datasource);
        $sql = $connection->createTable($this->table, $this->fields);
    
        return $connection->execute($sql);
    }

    /**
     * Inserts the records.
     */
    public function insert()
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
    public function drop()
    {
        $connection = ConnectionManager::get($this->datasource);

        return $connection->execute("DROP TABLE IF EXISTS {$this->table}");
    }

    /**
     * Truncates the table.
     *
     * @return bool true or false
     */
    public function truncate()
    {
        $connection = ConnectionManager::get($this->datasource);

        return $connection->execute("TRUNCATE {$this->table}");
    }
}
