<?php
/**
 * Database Configuration file
 * Add or remove connections here.
 */
use Origin\Model\ConnectionManager;

ConnectionManager::config('default', array(
  'host' => 'db',
  'database' => 'origin',
  'username' => 'origin',
  'password' => 'secret',
));

ConnectionManager::config('test', array(
  'host' => 'db',
  'database' => 'origin_test',
  'username' => 'origin',
  'password' => 'secret',
));
