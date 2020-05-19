<?php
use Origin\Model\Engine\MysqlEngine;
use Origin\Model\Engine\PgsqlEngine;

/**
 * Database configuration
 * Engines are mysql and pgsql
 * @link https://www.originphp.com/docs/getting-started/
 */

$engine = env('DB_ENGINE', 'mysql');

return [
    'default' => [
        'host' => env('DB_HOST', '127.0.0.1'),
        'database' => 'origin',
        'username' => env('DB_USERNAME'),
        'password' => env('DB_PASSWORD'),
        'className' => $engine === 'mysql' ? MysqlEngine::class : PgsqlEngine::class
    ],
    'test' => [
        'host' => env('DB_HOST', '127.0.0.1'),
        'database' => 'origin_test',
        'username' => env('DB_USERNAME'),
        'password' => env('DB_PASSWORD'),
        'className' => $engine === 'mysql' ? MysqlEngine::class : PgsqlEngine::class
    ]
];
