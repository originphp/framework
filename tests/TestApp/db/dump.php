<?php
use Origin\Model\Schema;

class DumpSchema extends Schema
{
    const VERSION = 20190808072330;

	public $posts = [
		'columns' => [
			'id' => ['type' => 'integer', 'limit' => 11, 'unsigned' => false, 'null' => false, 'default' => NULL, 'autoIncrement' => true],
			'title' => ['type' => 'string', 'limit' => 255, 'null' => false, 'default' => NULL, 'collate' => 'utf8mb4_0900_ai_ci'],
			'body' => ['type' => 'text', 'null' => true, 'default' => NULL, 'collate' => 'utf8mb4_0900_ai_ci'],
			'published' => ['type' => 'integer', 'limit' => 11, 'unsigned' => false, 'null' => false, 'default' => 0],
			'created' => ['type' => 'datetime', 'null' => true, 'default' => NULL],
			'modified' => ['type' => 'datetime', 'null' => true, 'default' => NULL]
		],
		'constraints' => [
			'primary' => ['type' => 'primary', 'column' => 'id']
		],
		'indexes' => [],
		'options' => ['engine' => 'InnoDB', 'collation' => 'utf8mb4_0900_ai_ci']
	];

}
