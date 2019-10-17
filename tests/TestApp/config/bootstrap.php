<?php
use Origin\Core\Plugin;
use Origin\Core\Autoloader;

require __DIR__ . '/paths.php';
require ORIGIN . '/src/bootstrap.php';

$autoloader = Autoloader::instance();
$autoloader->directory(dirname(__DIR__));

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
require 'routes.php';

/*
 * Load your plugins here
 * use Origin\Core\Plugin
 * @example Plugin::load('ContactManager');
 */
Plugin::load('Make');

/*
 * Initialize the plugins
 */
Plugin::initialize();
