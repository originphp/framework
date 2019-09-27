<?php
use Origin\Core\Plugin;
use Origin\Core\Autoloader;
use Origin\Utility\Elasticsearch;

$autoloader = Autoloader::instance();
$autoloader->directory(APP);

$namespaces = [
    'App' => 'app'
];

$autoloader->addNamespaces($namespaces);
$autoloader->register();

include 'application.php';
include 'log.php';
include 'cache.php';
include 'database.php';
include 'storage.php';
include 'email.php';
include 'queue.php';

if (env('ELASTICSEARCH_HOST')) {
    Elasticsearch::config('test', [
        'host' => env('ELASTICSEARCH_HOST'),
        'port' => 9200,
        'ssl' => false,
        'timeout' => 400,
    ]);
}

/*
 * Load your plugins here
 * use Origin\Core\Plugin
 * @example Plugin::load('ContactManager');
 */
Plugin::load('Make'); // This is for code gen you can remove

/*
 * Initialize the plugins
 */
Plugin::initialize();
