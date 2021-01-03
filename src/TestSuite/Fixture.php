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
declare(strict_types = 1);
namespace Origin\TestSuite;

use Origin\Core\HookTrait;
use Origin\Inflector\Inflector;
use Origin\Model\ConnectionManager;
use Origin\Model\Schema\TableSchema;

/**
 * The main goal of the fixture class is to insert records for each test. However, sometimes, the schema
 * will be needed to be created on the fly, so this also handles this. Features in Fixture have now become
 * obsolete since introducing the desired default behavior which is to use existing tables in the test database
 */
class Fixture
{
    use HookTrait;
    /**
     * The table name used by this fixture. It is
     * guessed using the class name. ArticleFixture treats table as articles
     *
     * @var string
     */
    protected $table = null;

    /**
     * Records to insert
     *
     * @var array
     */
    protected $records = [];

    /**
     * Drops and recreates tables between tests. Default behavior is always drop tables.
     * @var bool
     */
    protected $dropTables = true;

    /**
    * Use this to create a custom table, using the information retreived from Model:describe('table');
    * example:
    * protected $schema = [
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
    protected $schema = [];
    
    public function __construct()
    {
        if ($this->table === null) {
            list($namespace, $class) = namespaceSplit(get_class($this));
            $name = substr($class, 0, -7);
            $this->table = Inflector::tableName($name);
        }
        $this->executeHook('initialize');
    }

    /**
     * Gets the insertOnlyFlag
     *
     * @return bool
     */
    public function insertOnly(): bool
    {
        return empty($this->schema);
    }
   
    /**
     * Creates the table.
     *
     * @return void
     */
    public function create(): void
    {
        $connection = ConnectionManager::get('test');
        $table = new TableSchema($this->table, $this->schema['columns'], $this->schema);

        foreach ($table->toSql($connection) as $statement) {
            $connection->execute($statement);
        }
    }

    /**
     * Inserts the records.
     *
     * @return void
     */
    public function insert(): void
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
    public function drop(): bool
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
    public function truncate(): bool
    {
        $connection = ConnectionManager::get('test');
        
        $statements = $connection->adapter()->truncateTableSql($this->table);
        foreach ($statements as $sql) {
            $connection->execute($sql);
        }

        return true;
    }

    /**
     * Gets the drop tables flag
     *
     * @return boolean
     */
    public function dropTables(): bool
    {
        return $this->dropTables;
    }
}
