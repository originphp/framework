<?php
/**
 * Database Configuration file
 * Add or remove connections here.
 */
use Origin\Model\ConnectionManager;

ConnectionManager::config('default', [
    'host' => env('DB_HOST'),
    'database' => 'origin',
    'username' => env('DB_USERNAME'),
    'password' => env('DB_PASSWORD'),
    'engine' => env('DB_ENGINE'),
]);

ConnectionManager::config('test', [
    'host' => env('DB_HOST'),
    'database' => 'origin_test',
    'username' => env('DB_USERNAME'),
    'password' => env('DB_PASSWORD'),
    'engine' => env('DB_ENGINE'),
]);

/*

ConnectionManager::config('default', [
  'host' => 'pg',
  'database' => 'origin',
  'username' => 'root',
  'password' => 'root',
  'engine' => 'pgsql'
]);

ConnectionManager::config('test', [
  'host' => 'pg',
  'database' => 'origin_test',
  'username' => 'root',
  'password' => 'root',
  'engine' => 'pgsql'
]);

*/
