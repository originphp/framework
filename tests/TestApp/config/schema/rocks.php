<?php
$schema = array (
  'id' => 
  array (
    'type' => 'integer',
    'length' => 11,
    'default' => NULL,
    'null' => false,
    'key' => 'primary',
    'autoIncrement' => true,
  ),
  'name' => 
  array (
    'type' => 'string',
    'length' => 80,
    'default' => NULL,
    'null' => false,
  ),
  'created' => 
  array (
    'type' => 'datetime',
    'default' => NULL,
    'null' => false,
  ),
  'modified' => 
  array (
    'type' => 'datetime',
    'default' => NULL,
    'null' => false,
  ),
);