<?php
/**
 * Database configuration
 * Engines are mysql and pgsql
 * @see https://www.originphp.com/docs/getting-started/
 */
use Origin\Model\ConnectionManager;

ConnectionManager::config('default', [
    'host' => env('DB_HOST', '127.0.0.1'),
    'database' => 'origin',
    'username' => env('DB_USERNAME'),
    'password' => env('DB_PASSWORD'),
    'engine' => env('DB_ENGINE', 'mysql')
]);

ConnectionManager::config('test', [
    'host' => env('DB_HOST', '127.0.0.1'),
    'database' => 'origin_test',
    'username' => env('DB_USERNAME'),
    'password' => env('DB_PASSWORD'),
    'engine' => env('DB_ENGINE', 'mysql')
]);
