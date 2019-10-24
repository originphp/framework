<?php
/**
 * Log Configuration
 * Engines include File,Console,Email and Syslog
 * @see https://www.originphp.com/docs/log/
 */
use Origin\Log\Log;

Log::config('default', [
    'engine' => 'File',
    'file' => LOGS . '/application.log'
]);
