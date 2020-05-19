<?php

/**
 * Queue Configuration
 * Engines are Database and Redis
 * @link https://www.originphp.com/docs/queue/
 */

use Origin\Job\Engine\DatabaseEngine;

return [
    'default' => [
        'className' => DatabaseEngine::class,
        'connection' => 'default'
    ],
    'test' => [
        'className' => DatabaseEngine::class,
        'connection' => 'test'
    ]
];
