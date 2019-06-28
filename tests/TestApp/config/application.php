<?php
use Origin\Core\Configure;
use Origin\Core\Plugin;
use Origin\Cache\Cache;

/*
 * This will go in your server.php file once your app has been developed.
 */
Configure::write('debug', true); // goes in server

/*
 * If you change namespace name then you will need to change:
 *  1. The namespace declaration in all your files in the src folder
 *  2. config/autoloader.php folder
 */
Configure::write('App.namespace', 'App');
Configure::write('App.encoding', 'UTF-8');
Configure::write('Session.timeout', 3600);

Cache::config('default', ['engine' => 'File']);

/**
 * Generate a random string such as md5(time()) and place
 * here. This is used with hashing and key generation by Security.
 */
Configure::write('Security.pepper', '-----ORIGIN PHP-----');

/**
 * Recommended is true, if you don't want date,datetime,time or number fields to be parsed according
 * to the configured locale and timezone, then you can disable this. When you pass an array to new or patch
 * data goes through a marshalling process and this is where the parsing occurs.
 */
Configure::write('I18n.parse', true);

/*
 * Load your plugins here
 * @example Plugin::load('ContactManager');
 */
Plugin::load('Make'); // This is for code gen you can remove
