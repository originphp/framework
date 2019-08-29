<?php
use Origin\Job\Queue;
use Origin\Cache\Cache;
use Origin\Core\Config;
use Origin\Core\Plugin;
use Origin\Mailer\Email;
use Origin\Utility\Elasticsearch;

/*
 * This will go in your server.php file once your app has been developed.
 */
Config::write('debug', true); // goes in server

/*
 * If you change namespace name then you will need to change:
 *  1. The namespace declaration in all your files in the src folder
 *  2. config/autoloader.php folder
 */
Config::write('App.namespace', 'App');
Config::write('App.encoding', 'UTF-8');
Config::write('Session.timeout', 3600);

Cache::config('default', ['engine' => 'File']);

/**
 * Generate a random string such as md5(time()) and place
 * here. This is used with hashing and key generation by Security.
 */
Config::write('Security.pepper', '-----ORIGIN PHP-----');
Config::write('Cookie.key', md5('-----ORIGIN PHP-----')); // For testing

if (env('ELASTICSEARCH_HOST')) {
    Elasticsearch::config('test', [
        'host' => env('ELASTICSEARCH_HOST'),
        'port' => 9200,
        'ssl' => false,
        'timeout' => 400,
    ]);
}

Queue::config('test', [
    'engine' => 'Database',
    'datasource' => 'test',
]);

Email::config('test', [
    'debug' => true,
]);
/*
 * Load your plugins here
 * @example Plugin::load('ContactManager');
 */
Plugin::initialize();
Plugin::load('Make'); // This is for code gen you can remove
