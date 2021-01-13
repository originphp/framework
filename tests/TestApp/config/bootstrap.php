<?php

use Origin\Log\Log;
use Origin\Job\Queue;
use Origin\Cache\Cache;
use Origin\Core\Config;
use Origin\Core\Plugin;
use Origin\Email\Email;
use Origin\DotEnv\DotEnv;
use Origin\Core\Autoloader;
use Origin\Mailbox\Mailbox;
use Origin\Model\ConnectionManager;

require __DIR__ . '/paths.php';
require dirname(__DIR__, 3) . '/src/Core/bootstrap.php';

# # # START CUSTOM TEST APP CODE # # #
# 1. Load .env
# 2. Setup test App namespace
(new DotEnv())->load(__DIR__);
$autoloader = Autoloader::instance();
$autoloader->directory(dirname(__DIR__));
$namespaces = [
    'App' => 'app'
];
$autoloader->addNamespaces($namespaces);
$autoloader->register();

# # # END CUSTOM TEST APP CODE # # #

/**
 * Loads the config file, for example `log` will load `config/log.php`.
 * You can create your own configuration files and add them here
 *
 * @example
 * Config::load('stripe');
 * $token  = Config::read('Stripe.privateKey');
 */

Config::load('app');
Config::load('log');
Config::load('cache');
Config::load('database');
Config::load('storage');
Config::load('email');
Config::load('queue');
Config::load('mailbox');

/**
 * Configure the server
 */
mb_internal_encoding(Config::read('App.encoding'));
date_default_timezone_set(Config::read('App.defaultTimezone'));

/**
 * Configure individual components, configuration will be
 * consumed so it will no longer be available using Config::read()
 * but you can get from component, e.g. Log::config('key');
 */
Log::config(Config::consume('Log'));
Cache::config(Config::consume('Cache')); #! Import Disable
ConnectionManager::config(Config::consume('Database'));
#Storage::config(Config::consume('Storage')); #! Import Disable
Email::config(Config::consume('Email'));
Queue::config(Config::consume('Queue'));
Mailbox::config(Config::consume('Mailbox'));

/**
 * Load additional files here
 * @example
 * require __DIR__ .'/application.php';
 */

/*
 * Load your plugins here
 * use Origin\Core\Plugin
 * @example Plugin::load('ContactManager');
 */

Plugin::load('Make');

/*
 * Initialize plugins
 */
Plugin::initialize();

/**
 * Quick function to help go backwards
 */
function isPHP8(): bool
{
    return version_compare(PHP_VERSION, '8.0.0') >= 0;
}

/**
 * Load the routes after plugins have been loaded
 */
require CONFIG . '/routes.php';
