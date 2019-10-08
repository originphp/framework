<?php
use Origin\Model\Schema;

/**
 * @internal issue with postgresql not able to disable foreign key constraints.
 */
class ApplicationSchema extends Schema
{
    const VERSION = 20190808044021;

    protected $bookmarks = [
        'columns' => [
            'id' => ['type' => 'integer', 'limit' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'autoIncrement' => true],
            'user_id' => ['type' => 'integer', 'limit' => 11, 'unsigned' => false, 'null' => false, 'default' => null],
            'title' => ['type' => 'string', 'limit' => 50, 'null' => false, 'default' => null],
            'description' => ['type' => 'text', 'null' => true, 'default' => null],
            'url' => ['type' => 'text', 'null' => true, 'default' => null],
            'category' => ['type' => 'string', 'limit' => 80, 'null' => true, 'default' => null],
            'created' => ['type' => 'datetime', 'null' => false, 'default' => null],
            'modified' => ['type' => 'datetime', 'null' => false, 'default' => null],
        ],
        'constraints' => [
            'primary' => ['type' => 'primary', 'column' => 'id'],
            'bookmarks_ibfk_1' => ['type' => 'foreign', 'column' => 'user_id', 'references' => ['users', 'id']],
        ],
        'indexes' => [
            'user_id' => ['type' => 'index', 'column' => 'user_id'],
        ],
        'options' => ['engine' => 'InnoDB', 'collation' => 'utf8mb4_0900_ai_ci'],
    ];

    protected $bookmarks_tags = [
        'columns' => [
            'bookmark_id' => ['type' => 'integer', 'limit' => 11, 'unsigned' => false, 'null' => false, 'default' => null],
            'tag_id' => ['type' => 'integer', 'limit' => 11, 'unsigned' => false, 'null' => false, 'default' => null],
        ],
        'constraints' => [
            'primary' => ['type' => 'primary', 'column' => ['bookmark_id', 'tag_id']],
            'bookmarks_tags_ibfk_1' => ['type' => 'foreign', 'column' => 'tag_id', 'references' => ['tags', 'id']],
            'bookmarks_tags_ibfk_2' => ['type' => 'foreign', 'column' => 'bookmark_id', 'references' => ['bookmarks', 'id']],
        ],
        'indexes' => [
            'tag_id' => ['type' => 'index', 'column' => 'tag_id'],
        ],
        'options' => ['engine' => 'InnoDB', 'collation' => 'utf8mb4_0900_ai_ci'],
    ];

    protected $users = [
        'columns' => [
            'id' => ['type' => 'integer', 'limit' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'autoIncrement' => true],
            'name' => ['type' => 'string', 'limit' => 120, 'null' => false, 'default' => null],
            'email' => ['type' => 'string', 'limit' => 255, 'null' => false, 'default' => null],
            'password' => ['type' => 'string', 'limit' => 255, 'null' => false, 'default' => null],
            'dob' => ['type' => 'date', 'null' => true, 'default' => null],
            'created' => ['type' => 'datetime', 'null' => false, 'default' => null],
            'modified' => ['type' => 'datetime', 'null' => false, 'default' => null],
            'api_token' => ['type' => 'string', 'limit' => 40, 'null' => true, 'default' => null],
        ],
        'constraints' => [
            'primary' => ['type' => 'primary', 'column' => 'id'],
        ],
        'indexes' => [],
        'options' => ['engine' => 'InnoDB', 'collation' => 'utf8mb4_0900_ai_ci'],
    ];

    protected $tags = [
        'columns' => [
            'id' => ['type' => 'integer', 'limit' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'autoIncrement' => true],
            'title' => ['type' => 'string', 'limit' => 255, 'null' => false, 'default' => null],
            'created' => ['type' => 'datetime', 'null' => false, 'default' => null],
            'modified' => ['type' => 'datetime', 'null' => false, 'default' => null],
        ],
        'constraints' => [
            'primary' => ['type' => 'primary', 'column' => 'id'],
            'title' => ['type' => 'unique', 'column' => 'title'],
        ],
        'indexes' => [],
        'options' => ['engine' => 'InnoDB', 'collation' => 'utf8mb4_0900_ai_ci'],
    ];
}
