<?php
$schema = [
  'posts' => [
    'id' =>     [
      'type' => 'primaryKey',
      'limit' => NULL,
      'default' => NULL,
      'null' => false,
      'key' => 'primary',
    ],
    'title' =>     [
      'type' => 'string',
      'limit' => 255,
      'default' => NULL,
      'null' => false,
    ],
    'body' =>     [
      'type' => 'text',
      'limit' => NULL,
      'default' => NULL,
      'null' => true,
    ],
    'published' =>     [
      'type' => 'integer',
      'limit' => NULL,
      'default' => NULL,
      'null' => false,
    ],
    'created' =>     [
      'type' => 'datetime',
      'limit' => NULL,
      'default' => NULL,
      'null' => true,
    ],
    'modified' =>     [
      'type' => 'datetime',
      'limit' => NULL,
      'default' => NULL,
      'null' => true,
    ],
  ],
];