<?php

use Origin\Model\Engine\MysqlEngine;
use Origin\Model\Engine\SqliteEngine;
use Origin\Model\Engine\PostgresEngine;

/**
 * Database configuration
 * Engines are mysql and postgres
 * @link https://www.originphp.com/docs/getting-started/
 */

$engine = env('DB_ENGINE', 'mysql');

$engineMap = [
    'mysql' => MysqlEngine::class,
    'postgres' => PostgresEngine::class,
    'sqlite' => SqliteEngine::class
];

@unlink(ROOT . '/origin.db');
@unlink(ROOT . '/origin_test.db');

return [
    'default' => [
        'host' => env('DB_HOST', '127.0.0.1'),
        'database' => $engine === 'sqlite' ? ROOT . '/origin.db' : 'origin',
        'username' => env('DB_USERNAME'),
        'password' => env('DB_PASSWORD'),
        'className' => $engineMap[$engine]
    ],
    'test' => [
        'host' => env('DB_HOST', '127.0.0.1'),
        'database' => $engine === 'sqlite' ? ROOT . '/origin_test.db' : 'origin_test',
        'username' => env('DB_USERNAME'),
        'password' => env('DB_PASSWORD'),
        'className' => $engineMap[$engine]
    ]
];
