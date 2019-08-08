<?php
use Origin\Model\Schema;

class MigrationsSchema extends Schema
{
    const VERSION = 20190808104733;

    /**
     * Table name
     *
     * @var array
     */
    public $migrations = [
        'columns' => [
            'id' => ['type' => 'integer', 'limit' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'autoIncrement' => true],
            'version' => ['type' => 'string', 'limit' => 14, 'null' => false, 'default' => null, 'collate' => 'utf8mb4_0900_ai_ci'],
            'rollback' => ['type' => 'text', 'limit' => 16777215, 'null' => true, 'default' => null, 'collate' => 'utf8mb4_0900_ai_ci'],
            'created' => ['type' => 'datetime', 'null' => false, 'default' => null],
        ],
        'constraints' => [
            'primary' => ['type' => 'primary', 'column' => 'id'],
        ],
        'indexes' => [
            'migrations_version_index' => ['type' => 'index', 'column' => 'version'],
        ],
        'options' => ['engine' => 'InnoDB', 'collation' => 'utf8mb4_0900_ai_ci'],
    ];
}
