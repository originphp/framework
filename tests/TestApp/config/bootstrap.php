<?php
use Origin\Core\Config;
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

/**
 * Load environment vars
 */
if (file_exists(__DIR__ . '/.env.php')) {
    $result = require __DIR__ . '/.env.php';
    foreach ($result as $key => $value) {
        $_ENV[$key] = $value;
    }
}

require 'application.php';

mb_internal_encoding(Config::read('App.encoding'));
date_default_timezone_set(Config::read('App.defaultTimezone'));

require 'log.php';
require 'cache.php';
require 'database.php';
require 'storage.php';
require 'email.php';
require 'queue.php';
require 'mailbox.php';

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

require 'routes.php';
