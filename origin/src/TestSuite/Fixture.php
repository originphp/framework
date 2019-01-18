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

class Fixture
{
    public $datasource = 'test';

    public $table = null;

    public $fields = [];

    public $records = [];

    /**
     * You can import table schema from the model
     *
     * @var string
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
            $this->table = $this->tableFromClass(get_class($this));
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
        $table = Inflector::tableize($this->import);
        $datasource = 'default';

        $className = Resolver::className($this->import, 'Model');
        if ($className) {
            $model = new $className();
            $datasource = $model->datasource;
            $table = $model->table;
        }
      
        $connection = ConnectionManager::get($datasource);
        $connection->execute("SHOW CREATE TABLE {$table}");
        $result = $connection->fetch();
        if (!empty($result['Create Table'])) {
            $sql = $result['Create Table'];
            $connection = ConnectionManager::get($this->datasource);
            return $connection->execute($sql);
        }
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
        $Schema = new Schema();
        $sql = $Schema->createTable($this->table, $this->fields);

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

    /**
     * Gets table name from class name
     * Origin\Test\Fixture\ArticleFixture -> articles.
     *
     * @param string $class
     *
     * @return string table name
     */
    protected function tableFromClass(string $class)
    {
        list($namespace, $class) = namespaceSplit($class);
        $class = substr($class, 0, -7);
        return Inflector::tableize($class);
    }
}
