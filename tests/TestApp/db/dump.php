<?php
$schema = [
  'posts' => [
    'id' =>     [
      'type' => 'primaryKey',
      'limit' => 11,
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
      'default' => NULL,
      'null' => true,
    ],
    'published' =>     [
      'type' => 'integer',
      'limit' => 11,
      'default' => '0',
      'null' => false,
    ],
    'created' =>     [
      'type' => 'datetime',
      'default' => NULL,
      'null' => true,
    ],
    'modified' =>     [
      'type' => 'datetime',
      'default' => NULL,
      'null' => true,
    ],
  ],
];