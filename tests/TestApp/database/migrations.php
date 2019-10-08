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
    protected $migrations = [
        'columns' => [
            'id' => ['type' => 'integer', 'limit' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'autoIncrement' => true],
            'version' => ['type' => 'integer', 'limit' => 14, 'null' => false, 'default' => null],
            'rollback' => ['type' => 'text', 'limit' => 16777215, 'null' => true, 'default' => null],
            'created' => ['type' => 'datetime', 'null' => false, 'default' => null],
        ],
        'constraints' => [
            'primary' => ['type' => 'primary', 'column' => 'id'],
        ],
        'indexes' => [
            'migrations_version_index' => ['type' => 'index', 'column' => 'version'],
        ],
        'options' => ['engine' => 'InnoDB'],
    ];
}
