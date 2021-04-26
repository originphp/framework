<?php

/**
 * app.php configuration file. For server specific vars use the .env file.
 * The .env file will be parsed automatically and saved into .env.php. If you
 * make changes to the .env file, then delete .env.php and a new one will be
 * generated
 * To access settings from here
 *
 * use Origin\Core\Config;
 * $name = Config::read('App.name');
 */
return [
    /**
     * Debug level
     * Set to true to display error messages (Development mode)
     */
    'debug' => env('APP_DEBUG', true),
    'name' => 'Test Application',
    'url' => env('APP_URL', 'http://localhost'),
    'environment' => env('APP_ENV'),
    'namespace' => 'App',
    'encoding' => 'UTF-8',
    'defaultTimezone' => 'UTC',
    'securityKey' => env('APP_KEY'),
    'schemaFormat' => 'php',
    'mailboxKeepEmails' => '+30 days'
];
