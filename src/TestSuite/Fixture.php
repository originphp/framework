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

use Origin\Utility\Inflector;
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

    public function __construct()
    {
        if ($this->table === null) {
            list($namespace, $class) = namespaceSplit(get_class($this));
            $name = substr($class, 0, -7);
            $this->table = Inflector::tableName($name);
        }

        $this->initialize();
    }

    /**
     * Gets the insertOnlyFlag
     *
     * @return bool
     */
    public function insertOnly() :bool
    {
        return empty($this->schema);
    }

    /**
     * Use to create dynamic records.
     */
    public function initialize()
    {
    }
   
    /**
     * Creates the table.
     *
     * @return bool true or false
     */
    public function create() : bool
    {
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
        $connection = ConnectionManager::get('test');
   
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
        $connection = ConnectionManager::get('test');
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
        $connection = ConnectionManager::get('test');
        $sql = $connection->adapter()->truncateTableSql($this->table);

        return $connection->execute($sql);
    }
}
