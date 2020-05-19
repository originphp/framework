<?php

/**
 * Log Configuration
 * Engines include File,Console,Email and Syslog
 * @link https://www.originphp.com/docs/log/
 */

use Origin\Log\Engine\FileEngine;

return [
    'default' => [
        'className' => FileEngine::class,
        'file' => LOGS . '/application.log'
    ]
];
