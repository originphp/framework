<?php

use Origin\Cache\Engine\FileEngine;

return [
    'origin' => [
        'className' => FileEngine::class,
        'path' => CACHE . '/origin',
        'duration' => '+1 minutes',
        'prefix' => 'cache_',
        'serialize' => true
    ]
];
